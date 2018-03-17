<?php
namespace AppBundle\Command;

use AppBundle\Probe\DeviceDefinition;
use AppBundle\Probe\Message;
use AppBundle\Probe\MtrResponseFormatter;
use AppBundle\Probe\PingResponseFormatter;
use AppBundle\Probe\PingShellCommand;
use AppBundle\Probe\MtrShellCommand;
use AppBundle\Probe\WorkerResponse;
use AppBundle\ShellCommand\CommandFactory;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class ProbeWorkerCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected $rcv_buff;

    protected $tmp;

    protected $logger;
    protected $loop;
    protected $maxRunime;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:probe:worker')
            ->setDescription('Start the probe worker.')
            ->addOption(
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the command can run before terminating itself',
                0
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->maxRuntime              = $input->getOption('max-runtime');

        $this->loop = Factory::create();

        $read = new ReadableResourceStream(STDIN, $this->loop);

        $read->on('data', function ($data) {
            $this->rcv_buff .= $data;
            if (json_decode($this->rcv_buff, true)) {
                $this->tmp = $this->rcv_buff;
                $this->rcv_buff = "";
                $this->process($this->tmp);
            }
        });

        if ($this->maxRuntime > 0) {
            $this->logger->info("Running for ".$this->maxRuntime." seconds");
            $this->loop->addTimer($this->maxRuntime, function() use ($output) {
                $output->writeln("Max runtime reached");
                $this->loop->stop();
            });
        }

        $this->loop->run();
    }

    protected function process($data)
    {
        $timestamp = time();

        if (!trim($data)) {
            $this->sendResponse(array(
                'type' => 'exception',
                'status' => 400,
                'body' => array(
                    'timestamp' => $timestamp,
                    'contents' => 'Input data not received.'
                ),
                'debug' =>
                    array(
                        'runtime' => time() - $timestamp,
                        'request' => $data,
                        'pid' => getmypid(),
                    ),
                )
            );
            return;
        }

        $data = json_decode($data, true);

        if (!$data) {
            $this->sendResponse(array(
                    'type' => 'exception',
                    'status' => 400,
                    'body' => array(
                        'timestamp' => $timestamp,
                        'contents' => 'Invalid JSON Received.'
                    ),
                    'debug' =>
                        array(
                            'runtime' => time() - $timestamp,
                            'request' => $data,
                            'pid' => getmypid(),
                        ),
                )
            );
            return;
        }

        if (!isset($data['type'])) {
            $this->sendResponse(array(
                    'type' => 'exception',
                    'status' => 400,
                    'body' => array(
                        'timestamp' => $timestamp,
                        'contents' => 'Command type missing.'
                    ),
                    'debug' =>
                        array(
                            'runtime' => time() - $timestamp,
                            'request' => $data,
                            'pid' => getmypid(),
                        ),
                )
            );
            return;
        }

        $this->logger->info("COMMUNICATION_FLOW: Worker " . getmypid() . " received a " . $data['type'] . " instruction from master.");

        $factory = new CommandFactory();
        $data['container'] = $this->getContainer();
        $command = null;
        try {
            $command = $factory->create($data['type'], $data);
        } catch (\Exception $e) {
            $this->sendResponse(array(
                    'type' => 'exception',
                    'status' => 400,
                    'body' => array(
                        'timestamp' => $timestamp,
                        'contents' => $e->getMessage()
                    ),
                    'debug' =>
                        array(
                            'runtime' => time() - $timestamp,
                            'request' => $data,
                            'pid' => getmypid(),
                        ),
                )
            );
            return;
        }

        sleep($data['delay_execution']);

        try {
            $shellOutput = $command->execute();

            switch ($data['type']) {
                case 'post-result':
                    $this->sendResponse(array(
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => array(),
                        'body' => array(
                            'timestamp' => $timestamp,
                            'contents' => $shellOutput['contents'],
                            'raw' => $shellOutput,
                        ),
                        'debug' => array(
                            'runtime' => time() - $timestamp,
                            //'request' => $data,
                            'pid' => getmypid(),
                        ),
                    ));
                    break;

                case 'config-sync':
                    // This is a request to get the latest configuration from the master.
                    $this->sendResponse(array(
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => array(
                            'etag' => $shellOutput['etag'],
                        ),
                        'body' => array(
                            'timestamp' => $timestamp,
                            'contents' => $shellOutput['contents'],
                        ),
                        'debug' => array(
                            'runtime' => time() - $timestamp,
                            //'request' => $data,
                            'pid' => getmypid(),
                        ),
                    ));
                    break;

                case 'ping':
                case 'mtr':
                case 'traceroute':
                    $this->sendResponse(array(
                        'type' => 'probe',
                        'status' => 200,
                        'body' => array(
                            'timestamp' => $timestamp,
                            'contents' => array(
                                $data['id'] => array(
                                    'type' => $data['type'],
                                    'timestamp' => $timestamp,
                                    'targets' => $shellOutput,
                                )
                            ),
                        ),
                        'debug' => array(
                            'runtime' => time() - $timestamp,
                            //'request' => $data,
                            'pid' => getmypid(),
                        ),
                    ));
                    break;
            }

        } catch (\Exception $e) {
            $this->sendResponse(array(
                    'type' => 'exception',
                    'status' => 500,
                    'body' => array(
                        'timestamp' => $timestamp,
                        'contents' => $e->getMessage()
                    ),
                    'debug' =>
                        array(
                            'runtime' => time() - $timestamp,
                            'request' => $data,
                            'pid' => getmypid(),
                        ),
                )
            );
            return;
        }
    }

    protected function sendResponse($data)
    {
        $this->logger->info("COMMUNICATION_FLOW: Worker " . getmypid() . " sent a " . $data['type'] . " response.");
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}