<?php
namespace AppBundle\Command;

use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand
 * @package AppBundle\Command
 */
class ProbeDispatcherCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $processes = array();

    /**
     * @var array
     */
    protected $inputs = array();

    /** @var \SplQueue */
    protected $queue;

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queue = new \SplQueue();

        $pid = getmypid();
        $now = date('l jS \of F Y h:i:s A');

        $this->log($pid, "Started on $now");

        $slave = $this->getContainer()->getParameter('slave_id');

        $loop = Factory::create();

        $probeStore = $this->getContainer()->get('probe_store');

        $loop->addPeriodicTimer(15 * 60, function () use ($pid, $probeStore) {
            $this->log($pid, "Synchronizing ProbeStore.");
            $probeStore->sync();
        });

        // All of these actions should be non-blocking as we only dispatch actions towards our workers.
        $workerTimer = $loop->addPeriodicTimer(1, function () use ($pid, $probeStore) {
            foreach ($probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */
                $now = time();
                $remainder = $now % $probe->getStep();

                if ($remainder == 0) {
                    $probeType = $probe->getType();
                    $chunkSize = $probeType === 'mtr' ? 1 : 50;
                    $chunks = array_chunk($probe->getDevices(), $chunkSize);

                    foreach ($chunks as $devices) {
                        $worker = $this->getWorker();
                        $workerPid = $worker->getPid();
                        $input = $this->getInput($workerPid);

                        $ipAddresses = array_map(function (DeviceDefinition $device) {
                            return $device->getIp();
                        }, $devices);

                        $instruction = array(
                            'probeId' => $probe->getId(),
                            'command' => $probe->getType(),
                            'samples' => $probe->getSamples(),
                            'interval' => $probe->getInterval(),
                            'targets' => $ipAddresses,
                        );
                        $instruction = json_encode($instruction);

                        $this->log($pid, "Sending instruction to pid/$workerPid: $instruction");
                        $input->write($instruction);
                    }
                }
            }
        });

        $loop->addPeriodicTimer(10 * 60, function () {
            while (!$this->queue->isEmpty()) {
                $node = $this->queue->dequeue();
                $this->postResults($node);
            }
        });

        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        $process->checkTimeout();
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    $this->cleanup($pid);
                }
            }
        });

        $this->log($pid, "Synchronizing ProbeStore.");
        $probeStore->sync();

        $loop->run();
    }

    /**
     * @param $pid
     * @param $data
     */
    private function log($pid, $data)
    {
        $className = get_class($this);
        $now = date('Y/m/j H:i:s');
        echo "$now $className($pid) $data\n";
    }

    /**
     * Get a new Worker process.
     *
     * @return Process
     */
    private function getWorker()
    {
        // TODO: Remove verbosity.
        // TODO: Replace absolute path.
        $process = new Process("exec php /var/www/fireping/bin/console app:probe:worker -vvv");
        $input = new InputStream();
        $process->setInput($input);
        $process->setTimeout(180);
        $process->setIdleTimeout(60);
        $process->start(function ($type, $data) use ($process) {
            $pid = $process->getPid();
            $this->handleResponse($pid, $type, $data);
            $this->log(0, "Killing Process/$pid");
            $process->stop(3, SIGINT);
        });
        $pid = $process->getPid();
        $this->log(0,"[Process/$pid] Started");

        $this->processes[$pid] = $process;
        $this->inputs[$pid] = $input;

        return $process;
    }

    /**
     * Handler function for any incoming responses. This function either ignores responses if they are improperly
     * formatted, or delegates them to another function depending on certain data.
     *
     * @param $pid
     * @param $type
     * @param $data
     */
    private function handleResponse($pid, $type, $data)
    {
        $probeStore = $this->getContainer()->get('probe_store');

        $decoded = json_decode($data, true);

        if (!$decoded) {
            $this->log($pid, "Error: could not decode to json: $data");
            return;
        }

        if (!isset($decoded['probeId'], $decoded['timestamp'], $decoded['type'])) {
            $this->log($pid, "Error: missing parameter in decoded json object:");
            var_dump($decoded);
            return;
        }

        $probeId = $decoded['probeId'];
        $probe = $probeStore->getProbeById($probeId);

        if (!$probe) {
            $this->log($pid, "Error: could not find probe.");
            return;
        }

        $probeType = $decoded['type'];
        $formatted = array();
        switch ($probeType) {
            case 'ping':
                $formatted = $this->handlePingResponse($probe, $type, $decoded);
                break;
            case 'mtr':
                $formatted = $this->handleMtrResponse($probe, $type, $decoded);
                break;
            default:
                $this->log($pid, "Error: $probeType response handling not implemented.");
        }

        $this->log(0, "Info: Attempting to Post Results.");
        $this->postResults($formatted);
    }

    /**
     * Formats the output of the fping command to a proper format for the master.
     *
     * @param ProbeDefinition $probe
     * @param $type
     * @param $data
     * @return array
     */
    private function handlePingResponse(ProbeDefinition $probe, $type, $data)
    {
        $probeId = $probe->getId();

        $formatted = array(
            $probeId => array(
                'type' => $data['type'],
                'timestamp' => $data['timestamp'],
                'targets' => array(),
            ),
        );

        foreach ($data['return'] as $result) {
            $deviceId = $probe->getDeviceByIp(trim($result['ip']));
            if (!$deviceId) {
                $this->log(0, "Warning: Device/$deviceId was already removed from Probe/$probeId.\n");
            }
            $formatted[$probeId]['targets'][$deviceId] = $result['result'];
        }

        return $formatted;
    }

    private function handleMtrResponse(ProbeDefinition $probe, $type, $data)
    {
        $probeId = $probe->getId();

        $formatted = array(
            $probeId => array(
                'type' => $data['type'],
                'timestamp' => $data['timestamp'],
                'targets' => array(),
            ),
        );

        $deviceId = $probe->getDeviceByIp($data['return']['ip']);

        if (!$deviceId) {
            $this->log(0, "Warning: Device/$deviceId was already removed from Probe/$probeId.");
        }

        $formatted[$probeId]['targets'][$deviceId] = $data['return']['result'];

        return $formatted;
    }

    /**
     * Posts the formatted results from any probe to the master.
     *
     * @param array $results
     */
    private function postResults(array $results)
    {
        $this->log(0, 'Building Client...');
        $client = new Client();

        /*$this->log(0, 'Building Promise...');
        $promise = $client->postAsync('https://smokeping-dev.cegeka.be/api/slaves/1/result', [
            'json' => $results,
        ]);
        $promise->then(
            function (ResponseInterface $response) {
                $this->log(0, 'Received Response...');
                $statusCode = $response->getStatusCode();
                $body = $response->getBody();
                $this->log(0, "Info: code=$statusCode, body=$body");
            },
            function (RequestException $exception) {
                $this->log(0, 'Received Error...');
                $message = $exception->getMessage();
                $this->log(0, "Error: message=$message");
            }
        );*/
        /*try {
            $response = $client->post('https://smokeping-dev.cegeka.be/api/slaves/1/result', [
                'body' => json_encode($results),
            ]);
        } catch (TransferException $exception) {
            $this->queue->enqueue($results);
            $message = $exception->getMessage();
            $this->log(0, "Exception while pushing results: $message.");
        }

        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        $this->log(0, "Response code=$statusCode, body=$body");*/
        $this->log(0, "Info: Dumping results array.");
        var_dump(json_encode($results));
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param $id
     * @return mixed
     */
    private function getInput($id) {
        if (!isset($this->inputs[$id])) {
            $this->inputs[$id] = new InputStream();
        }
        return $this->inputs[$id];
    }

    /**
     * Dereferences old processes and inputs.
     *
     * @param $id
     */
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