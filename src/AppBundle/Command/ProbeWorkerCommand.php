<?php
namespace AppBundle\Command;

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

        $loop = \React\EventLoop\Factory::create();

        $read = new \React\Stream\ReadableResourceStream(STDIN, $loop);

        $read->on('data', function ($data) {
            $this->processData($data);
        });

        $loop->run();
    }

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
                // This is gonna be 'ping' instead of 'fping'.
                case 'fping':
                    $timestamp = time();
                    $results = $this->fping($data->targets);
                    $this->sendResponse(array(
                        'status' => 200,
                        'timestamp' => $timestamp,
                        'type' => $data->command,
                        'probe_id' => $data->probe_id,
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
     */
    protected function fping($targets, $pauseInterval = 1, $count = 2, $quiet = true)
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

        $output = array();
        foreach ($out as $target) {
            list ($ip, $result) = explode(' : ', $target);
            $sub = array(
                "ip" => $ip,
                "result" => $result,
            );
            $output[] = $sub;
        }

        return $output;
    }

    protected function sendResponse($data)
    {
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}