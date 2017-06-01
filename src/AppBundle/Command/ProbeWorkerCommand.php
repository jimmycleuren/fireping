<?php
namespace AppBundle\Command;

use AppBundle\Probe\PingResponseFormatter;
use AppBundle\Probe\PingShellCommand;
use AppBundle\Probe\MtrShellCommand;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
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

        $data = json_decode($rawData);
        if (!$data) {
            $this->sendResponse(array('return' => "Error Processing Data."));
        } else {
            switch ($data->command) {
                // TODO: API will return 'ping' instead of 'fping' soon.
                case 'fping':
                    $timestamp = time();
                    $samples = $data->samples;
                    $results = $this->fping($data->targets, $samples);
                    $this->sendResponse(array(
                        'status' => 200,
                        'timestamp' => $timestamp,
                        'type' => $data->command,
                        'probeId' => $data->probeId,
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
    protected function fping($targets, $count = 2, $pauseInterval = 1, $quiet = true)
    {
        $exec = new PingShellCommand($targets, $count, $pauseInterval);
        $out = $exec->execute();
        $formatter = new PingResponseFormatter();
        return $formatter->format($out);
    }

    /**
     * @param $targets
     * @param int $pauseInterval the amount of time in seconds to wait between each icmp echo-request.
     * @param int $count the amount of icmp echo-requests that will be sent to each target.
     * @param bool $quiet show only aggregate results.
     * @return array|\RuntimeException
     */
    protected function fping2($targets, $count = 2, $pauseInterval = 1, $quiet = true)
    {
        $finder = new ExecutableFinder();
        if (!$finder->find('fping')) {
            throw new \RuntimeException('Probe does not have fping installed.');
        }

        $pauseInterval *= 1000; // Converting seconds to milliseconds for fping.
        $command = "fping -C $count -p $pauseInterval";
        if ($quiet) {
            $command .= " -q";
        }
        $command .= " ";
        $command .= implode(" ", $targets);
        $command .= " 2>&1";

        $out = '';
        exec($command, $out);

        /*
         * This returns an output in the format of:
         * (
         *  'ip': 'ip-addr',
         *  'results': '1 2 3 4 -1 5 -1 3'
         * )
         */
        $output = array();
        foreach ((array) $out as $target) {
            list ($ip, $result) = explode(' : ', $target);
            $sub = array(
                "ip" => $ip,
                "result" => $result,
            );
            $output[] = $sub;
        }

        return $output;
    }

    /**
     * @param $target
     * @param int $samples default behaviour of mtr is to send 10 icmp echo-requests, we mimic that here.
     */
    protected function mtr($target, $samples = 10)
    {
        $mtr = new MtrShellCommand($target, $samples);
        $out = $mtr->execute();

        $output = array(
            'ip' => $out['report']['mtr']['dst'],
            'result' => $out['report']['hubs'],
        );

        return $output;
    }

    /**
     * @param $target
     * @param int $samples default behaviour of mtr is to send 10 icmp echo-requests, we mimic that here.
     */
    protected function mtr2($target, $samples = 10)
    {
        $finder = new ExecutableFinder();
        if (!$finder->find('mtr')) {
            throw new \RuntimeException('Probe does not have mtr installed.');
        }

        if (count($target) != 1) {
            throw new \RuntimeException('More than one target given.');
        }

        $target = $target[0];

        $command = "mtr -n -c $samples $target --json 2>&1";

        $out = ''; // this will be in JSON format.
        exec($command, $out); // this returns an array of strings for each line of the response.

        $out = implode("\n", $out); // Glue the array of strings back together.

        $decoded = json_decode($out, true);

        $output = array(
            'ip' => $decoded['report']['mtr']['dst'],
            'result' => $decoded['report']['hubs'],
        );

        return $output;
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}