<?php
namespace AppBundle\DependencyInjection;

use GuzzleHttp\Client;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProbeStore
 * @package AppBundle\DependencyInjection
 */
class ProbeStore
{
    /**
     * @var ContainerInterface
     */
    private   $container;
    protected $probes = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function addProbe(ProbeDefinition $probe)
    {
        $this->probes[] = $probe;
    }

    public function getProbes()
    {
        return $this->probes;
    }

    public function getProbeById($id)
    {
        foreach ($this->probes as $probe) {
            if ($probe->getId() === $id) {
                return $probe;
            }
        }
        return null;
    }

    public function getProbe($id, $type, $step, $samples)
    {
        foreach ($this->probes as $probe) {
            if ($probe->getId() === $id
                && $probe->getType() === $type
                && $probe->getStep() === $step
                && $probe->getSamples() === $samples) {
                return $probe;
            }
        }
        $newProbe = new ProbeDefinition($id, $type, $step, $samples);
        $this->addProbe($newProbe);
        return $newProbe;
    }

    private function deactivateAllDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            $probe->deactivateAllDevices();
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            $id = $probe->getId();
            $probe->purgeAllInactiveDevices();
        }
    }

    public function async(LoggerInterface $logger)
    {
        $client = new Client();

        $id = $this->container->getParameter('slave.id');
        $promise = $client->requestAsync('GET', "https://smokeping-dev.cegeka.be/api/slaves/$id/config");
        $logger->info("Created promise for config request.");
        $promise->then(function(ResponseInterface $response) use ($logger) {
            $logger->info("Received response to config request.");
            $configuration = json_decode($response->getBody(), true);

            if (!$configuration) {
                // Do something with this and abort.
                $logger->error("Non-JSON reply from configuration endpoint: " . $response->getBody());
            } else {
                $logger->info("Applying new configuration.");
                $this->updateConfig($configuration);
            }
        });
    }

    private function updateConfig($configuration) {
        $this->deactivateAllDevices();
        foreach ($configuration as $id => $probeConfig)
        {
            // TODO: More checks to make sure all of this data is here?
            $type = $probeConfig['type'];
            $step = $probeConfig['step'];
            $samples = $probeConfig['samples'];
            // TODO: Only type, step and targets needs to exist for operational.
            // Anything else is custom configuration.

            $probe = $this->getProbe($id, $type, $step, $samples);
            foreach ($probeConfig['targets'] as $hostname => $ip)
            {
                $device = new DeviceDefinition($hostname, $ip);
                $probe->addDevice($device);
            }
        }
        $this->purgeAllInactiveDevices();
    }

    public function sync()
    {
        $client = new Client();
        // TODO: Process Async
        $id = $this->container->getParameter('slave.id');
        $prod_endpoint = "https://smokeping-dev.cegeka.be/api/slaves/$id/config";
        $dev_endpoint = "http://localhost/api/slaves/$id/config";
        $endpoint = $prod_endpoint;
        $result = '';

        try {
            $result = $client->get($endpoint);
        } catch (TransferException $exception) {
            // TODO: Log this failure!
        }

        $decoded = json_decode($result->getBody(), true);

        if (!$decoded) {
            // TODO: Log this failure! Something wrong with the response body.
        } else {
            $this->deactivateAllDevices();
            foreach ($decoded as $id => $probeConfig)
            {
                // TODO: More checks to make sure all of this data is here?
                $type = $probeConfig['type'];
                $step = $probeConfig['step'];
                $samples = $probeConfig['samples'];
                // TODO: Only type, step and targets needs to exist for operational.
                // Anything else is custom configuration.

                $probe = $this->getProbe($id, $type, $step, $samples);
                foreach ($probeConfig['targets'] as $hostname => $ip)
                {
                    $device = new DeviceDefinition($hostname, $ip);
                    $probe->addDevice($device);
                }
            }
            $this->purgeAllInactiveDevices();
        }
    }

    public function printDevices()
    {
        foreach ($this->getProbes() as $probe)
        {
            print("[Probe:".$probe->getId()."] Devices:\n");
            print_r($probe->getDevices());
        }
    }
}