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

    /**
     * @return null
     */
    public function getEtag()
    {
        return $this->etag;
    }

    /**
     * @param null $etag
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
    }

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

    public function getProbe($id, $type, $step, $samples, $args = null)
    {
        foreach ($this->probes as $probe) {
            if ($probe->getId() === $id) {
                $probe->setConfiguration($id, $type, $step, $samples, $args);
                return $probe;
            }
        }
        $newProbe = new ProbeDefinition($id, $type, $step, $samples, $args);
        $this->addProbe($newProbe);
        return $newProbe;
    }

    public function getProbeDeviceCount($id)
    {
        /* @var $probe ProbeDefinition */
        $probe = $this->getProbeById($id);
        if ($probe === null) {
            return 0;
        }

        return count($probe->getDevices());
    }

    public function getAllProbesDeviceCount()
    {
        $total = 0;
        foreach ($this->getProbes() as $probe) {
            /* @var $probe ProbeDefinition */
            $total += count($probe->getDevices());
        }
        return $total;
    }

    private function deactivateAllDevices()
    {
        foreach ($this->getProbes() as $probe) {
            $probe->deactivateAllDevices();
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->getProbes() as $probe) {
            $probe->purgeAllInactiveDevices();
        }
    }

    public function updateConfig($configuration, $etag = null) {
        $this->deactivateAllDevices();
        foreach ($configuration as $id => $probeConfig) {
            // TODO: More checks to make sure all of this data is here?

            $type = $probeConfig['type'];
            $step = $probeConfig['step'];
            $samples = $probeConfig['samples'];
            $args = isset($probeConfig['args']) ? $probeConfig['args'] : null;

            $probe = $this->getProbe($id, $type, $step, $samples, $args);
            foreach ($probeConfig['targets'] as $hostname => $ip) {
                $device = new DeviceDefinition($hostname, $ip);
                $probe->addDevice($device);
            }

            // TODO: clean up, handle via master.
            if ($type === 'ping') {
                $probe->setArg('retries', 0);
                $t_wait = intval($step / $samples) * 1000;
                $n_devs = intval(count($probe->getDevices()));
                $interval = $t_wait / $n_devs;
                $probe->setArg('interval', $interval);
            }
        }
        $this->purgeAllInactiveDevices();
        $this->setEtag($etag);
    }

    public function sync(LoggerInterface $logger)
    {
        $start = microtime();
        $logger->info("Started syncing configuration.");
        /** @var \GuzzleHttp\Client $client */
        $client = $this->container->get('guzzle.client.api_fireping');

        $id = $this->container->getParameter('slave.name');
        $endpoint = "/api/slaves/$id/config";

        try {
            $request = isset($this->etag) ?
                new Request('GET', $endpoint, ['If-None-Match' => $this->etag]) :
                new Request('GET', $endpoint);

            $response = $client->send($request);

            $logger->info("Configuration retrieved from master.", array('duration' => microtime() - $start));
            $start = microtime();

            $etag = $response->hasHeader('ETag') ? $response->getHeader('ETag')[0] : null;

            $stats = array();
            foreach ($this->getProbes() as $probe) {
                /* @var $probe \AppBundle\Probe\ProbeDefinition */
                $stats[$probe->getId()] = $this->getProbeDeviceCount($probe->getId());
            }

            $stats['probes'] = count($this->getProbes());
            $stats['total_devs'] = $this->getAllProbesDeviceCount();

            if ($response->getStatusCode() === 304) {
                $logger->info("Configuration has not changed.", $stats);
                return null;
            }

            $logger->info("Parsed configuration statistics.", array('duration' => microtime() - $start));
            $start = microtime();

            $logger->info("Reloading configuration...", $stats);

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

            $logger->info("Reloaded running-configuration.", array('duration' => microtime() - $start));
            $start = microtime();

            $stats = array();
            foreach ($this->getProbes() as $probe) {
                /* @var $probe \AppBundle\Probe\ProbeDefinition */
                $stats[$probe->getId()] = $this->getProbeDeviceCount($probe->getId());
            }

            $stats['probes'] = count($this->getProbes());
            $stats['total_devs'] = $this->getAllProbesDeviceCount();

            $logger->info("New configuration stats calculated.", array('duration' => microtime() - $start));

            $logger->info("Configuration sync completed", $stats);
        } catch (TransferException $e) {
            $logger->error("Response (" . $e->getCode() . ") while retrieving configuration from $endpoint: " . $e->getMessage());
        }
    }
}