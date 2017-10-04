<?php
namespace AppBundle\Command;

use AppBundle\Probe\DeviceDefinition;
use AppBundle\Probe\Message;
use AppBundle\Probe\MtrResponseFormatter;
use AppBundle\Probe\PingResponseFormatter;
use AppBundle\Probe\PingShellCommand;
use AppBundle\Probe\MtrShellCommand;
use AppBundle\Probe\WorkerResponse;
use AppBundle\ShellCommand\ShellCommandFactory;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class ProbeWorkerCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected $rcv_buff;

    protected function configure()
    {
        $this
            ->setName('app:probe:worker')
            ->setDescription('Start the probe worker.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $loop = Factory::create();

        $read = new ReadableResourceStream(STDIN, $loop);

        $read->on('data', function ($data) {
            $this->rcv_buff .= $data;
            if (json_decode($this->rcv_buff, true)) {
                $this->process($this->rcv_buff);
                $this->rcv_buff = "";
            }
        });

        $loop->run();
    }

    protected function process($data)
    {
        $timestamp = time();

        if (!trim($data)) {
            $this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => "No input data received",
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                ),
                'debug' => array(
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                )
            ));
            return;
        }

        $data = json_decode($data, true);
        if (!$data) {
            $this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => 'Invalid JSON received',
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                ),
                'debug' => array(
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                )
            ));
            return;
        }

        if (!isset($data['type'])) {
            $this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => "Type key not set",
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                ),
                'debug' => array(
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                )
            ));
            return;
        }

        $factory = new ShellCommandFactory();
        $command = null;
        try {
            $command = $factory->create($data['type'], $data);
        } catch (\Exception $e) {
            $this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => $e->getMessage(),
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                ),
            ));
            return;
        }

        sleep($data['delay_execution']);

        try {
            $shellOutput = $command->execute();

            $requestedDevices = array_keys($data['targets']);
            $returningDevices = $shellOutput;

            $this->sendResponse(array(
                'status' => 200,
                'message' => 'OK',
                'body' => array(
                    $data['id'] => array(
                        'type' => $data['type'],
                        'timestamp' => $timestamp,
                        'targets' => $shellOutput,
                        'runtime' => time() - $timestamp,
                        'request_data' => $data,
                        'pid' => getmypid(),
                    ),
                ),
                'debug' => array(
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                    'req_dev' => count($requestedDevices),
                    'ret_dev' => count($returningDevices),
                )
            ));
            return;

        } catch (\Exception $e) {
            $this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => $e->getMessage(),
                ),
                'debug' => array(
                    '_exception' => $e->getMessage(),
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                )
            ));
            return;
        }
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}