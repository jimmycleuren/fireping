<?php
namespace AppBundle\DependencyInjection;

use GuzzleHttp\Client;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;
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
    protected $etag = null;

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
            if ($probe->getId() === $id) {
                $probe->setConfiguration($id, $type, $step, $samples);
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

    public function sync(LoggerInterface $logger)
    {
        $client = new Client();

        $id = $this->container->getParameter('slave.name');

        $prod_endpoint = "https://smokeping-dev.cegeka.be/api/slaves/$id/config";
        $dev_endpoint = "http://localhost/api/slaves/$id/config";
        $endpoint = $prod_endpoint;

        try {
            $request = isset($this->etag) ?
                new Request('GET', $endpoint, ['If-None-Match' => $this->etag]) :
                new Request('GET', $endpoint);

            $response = $client->send($request);

            $etag = $response->hasHeader('ETag') ? $response->getHeader('ETag')[0] : null;

            if ($response->getStatusCode() === 304) {
                $logger->info("Configuration has not changed.");
                return null;
            }

            $logger->info("Reloading configuration...");

            $configuration = json_decode($response->getBody()->getContents(), true);

            if ($configuration === null) {
                $logger->error("Master is returning non-JSON.");
                return null;
            }

            if (count($configuration) === 0) {
                $logger->info("Empty configuration received.");
                $this->probes = array();
                return null;
            }

            $this->updateConfig($configuration);

            $this->etag = $etag;
        } catch (TransferException $e) {
            $logger->error("Response (" . $e->getCode() . ") while retrieving configuration from $endpoint: " . $e->getMessage());
        }
    }
}