<?php
namespace AppBundle\Command;

use AppBundle\Probe\DeviceDefinition;
use AppBundle\Probe\MtrResponseFormatter;
use AppBundle\Probe\PingResponseFormatter;
use AppBundle\Probe\PingShellCommand;
use AppBundle\Probe\MtrShellCommand;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class ProbeWorkerCommand extends ContainerAwareCommand
{
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
            $this->processData($data);
        });

        $loop->run();
    }

    /*
     *
     */
    protected function processData($rawData)
    {
        if (!trim($rawData)) {
            return;
        }

        $data = json_decode($rawData, true);
        if (!$data) {
            $this->sendResponse(array('return' => "Error Processing Data."));
        } else {
            switch ($data['command']) {
                case 'ping':
                    $timestamp = time();
                    $samples = $data['samples'];
                    $results = $this->fping($data['targets'], $samples);
                    $this->sendResponse(array(
                        'status' => 200,
                        'timestamp' => $timestamp,
                        'type' => $data['command'],
                        'probeId' => $data['probeId'],
                        'return' => $results,
                    ));
                    break;
                case 'mtr':
                    $timestamp = time();
                    $results = $this->mtr($data->targets);
                    $this->sendResponse(array(
                        'status' => 200,
                        'timestamp' => $timestamp,
                        'type' => $data->command,
                        'probeId' => $data->probeId,
                        'return' => $results,
                    ));
                    break;
                default:
                    $this->sendResponse(array(
                        'status' => 404,
                        'return' => 'Command ' . $data->command . " not found.\n",
                    ));
            }
        }
    }

    /**
     * @param $targets
     * @param int $pauseInterval the amount of time in seconds to wait between each icmp echo-request.
     * @param int $count the amount of icmp echo-requests that will be sent to each target.
     * @param bool $quiet show only aggregate results.
     * @return array|\RuntimeException
     */
    protected function fping($targets, $count = 2, $pauseInterval = 1)
    {
        $ipAddresses = array_map(function ($device) {
            return $device['ip'];
        }, $targets);
        $exec = new PingShellCommand($ipAddresses, $count, $pauseInterval);
        $out = $exec->execute();
        $output = array();
        foreach ((array) $out as $key => $result) {
            list ($ip, $result) = explode(" : ", $result);
            $result = str_replace("-", "-1", $result);
            $result = explode(" ", $result);
            $deviceId = $targets[$key]['id'];
            $output[$deviceId] = $result;
        }
        return $output;
        //$formatter = new PingResponseFormatter();
        //return $formatter->format($out);
    }

    /**
     * @param $target
     * @param int $samples default behaviour of mtr is to send 10 icmp echo-requests, we mimic that here.
     */
    protected function mtr($target, $samples = 10)
    {
        $mtr = new MtrShellCommand($target, $samples);
        $out = $mtr->execute();
        $formatter = new MtrResponseFormatter();
        return $formatter->format($out);
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}