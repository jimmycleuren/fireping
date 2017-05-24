<?php
namespace AppBundle\Command;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use React\Socket\Server;

class ProbeDispatcherCommand extends ContainerAwareCommand
{
    protected $processes = array();
    protected $inputs = array();

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slave_id = $this->getContainer()->getParameter('slave_id');
        echo time() . ": dispatcher started for slave $slave_id.\n";
        $loop = \React\EventLoop\Factory::create();

        $loop->addTimer(24 * 60, function () {
            echo time() . ": dispatcher running for 24 hours; aborting.\n";
            exit();
        });

        echo time() . ": fetcher ProbeStore service.\n";
        $ps = $this->getContainer()->get('probe_store');

        $loop->addPeriodicTimer(15 * 60, function () use ($ps) {
            echo time() . ": synchronizing config.\n";
            $ps->sync();
        });

        $loop->addPeriodicTimer(1, function () use ($ps) {
            foreach ($ps->getProbes() as $probe) {
                $id = $probe->getId();
                $current_time = time(); // 14214124124921
                $remaining = $current_time % $probe->getInterval();
                if ($remaining == 0) {
                    echo time() . ": sending job to probe[$id].\n";
                    $proc = $this->getProcess($id);
                    $input = $this->getInput($id);
                    // get data to write and write it.
                    $chunks = array_chunk($probe->getDevices(), 50);
                    foreach ($chunks as $devices) {
                        $ips = array_map(function ($device) {
                            return $device->getIp();
                        }, $devices);
                        $instruction = array(
                            'probe_id' => $probe->getId(),
                            'command' => $probe->getType(),
                            'samples' => $probe->getSamples(),
                            'interval' => $probe->getInterval(),
                            'targets' => $ips,
                        );
                        $instruction = json_encode($instruction);
                        echo time() . ": instructing probe[$id]: $instruction\n";
                        $input->write($instruction);
                    }
                }
            }
        });

        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $id => $proc) {
                try {
                    if ($proc) {
                        $proc->checkTimeout();
                        $proc->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $e) {
                    $this->cleanup($id);
                }
            }
        });

        echo time() . ": initial config sync.\n";
        $ps->sync();

        $loop->run();
    }

    /*
     * Gets or creates a new process for a given Probe ID.
     */
    private function getProcess($id) {
        if (!isset($this->processes[$id])) {
            $this->processes[$id] = new Process("exec php /var/www/fireping/bin/console app:probe:worker");
            $input = $this->getInput($id);
            $this->processes[$id]->setInput($input);
            $this->processes[$id]->setTimeout(180); // Necessary? Every Probe has one process that runs for infinite time.
            $this->processes[$id]->setIdleTimeout(60);
            $this->processes[$id]->start(function ($type, $data) use ($id) {
                $this->handleResponse($id, $type, $data);
            });
        }
        return $this->processes[$id];
    }

    private function handleResponse($pid, $type, $data)
    {
        $ps = $this->getContainer()->get('probe_store');

        $decoded = json_decode($data, true);
        print_r($decoded);

        if (!$decoded) {
            echo "Invalid JSON.\n";
        }

        if ($decoded['status'] != 200) {
            echo "Worker did not return OK.\n";
            return;
        }

        $probe = $ps->getProbeById($decoded['probe_id']);
        $probe_id = $decoded['probe_id'];

        if (!$probe) {
            echo "No probe was found.\n";
        }

        $formatted = array(
            $probe_id => array(
                'type' => $decoded['type'],
                'timestamp' => $decoded['timestamp'],
                'targets' => array(),
            )
        );

        foreach ($decoded['return'] as $result) {
            $device_id = $probe->getDeviceByIp($result['ip']);
            if (!$device_id) {
                echo "Device already removed from probe, ignoring.\n";
            }
            $formatted[$probe_id]['targets'][$device_id] = $this->transformResult($result['result']);
        }

//        This will work next week.
//        $client = new Client();
//        $client->post('http://smokeping-dev.cegeka.be/api/slaves/'. $slave_id .'/result', [
//            'body' => json_encode($formatted);
//        ]);

        echo json_encode($formatted) . "\n";
    }

    private function transformResult($input)
    {
        $dashes = str_replace("-", "-1", $input);
        return explode(" ", $dashes);
    }

    /*
     * Gets or creates a new input stream.
     */
    private function getInput($id) {
        if (!isset($this->inputs[$id])) {
            $this->inputs[$id] = new InputStream();
        }
        return $this->inputs[$id];
    }

    private function cleanup($id)
    {
        if (isset($this->processes[$id])) {
            $this->processes[$id]->stop(0);
            $this->processes[$id] = null;
            unset($this->processes[$id]);
        }

        if (isset($this->inputs[$id])) {
            $this->inputs[$id] = null;
            unset($this->inputs[$id]);
        }
    }
}