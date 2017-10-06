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
            /*$this->sendResponse(array(
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
            return;*/
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
            /*$this->sendResponse(array(
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
            return;*/
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
            /*$this->sendResponse(array(
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
            return;*/
        }

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
            /*$this->sendResponse(array(
                'status' => 500,
                'message' => 'NOK',
                'body' => array(
                    '_exception' => $e->getMessage(),
                    'runtime' => time() - $timestamp,
                    'request_data' => $data,
                    'pid' => getmypid(),
                ),
            ));
            return;*/
        }

        sleep($data['delay_execution']);

        try {
            $shellOutput = $command->execute();

            switch ($data['type']) {
                case 'post-result':
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
                            'request' => $data,
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
                            'request' => $data,
                            'pid' => getmypid(),
                        ),
                    ));
                    break;
            }

            /*if ($data['type'] === 'http') {
                $this->sendResponse(array(
                    'status' => 200,
                    'message' => 'Sync OK',
                    'body' => array(
                        'type' => $data['type'],
                        'timestamp' => $timestamp,
                        'contents' => $shellOutput,
                        'request_data' => $data,
                        'pid' => getmypid(),
                    )
                ));
                return;

            } else {
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
                    )
                ));
                return;
            }*/

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

            /*$this->sendResponse(array(
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
            return;*/
        }
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}