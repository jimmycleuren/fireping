<?php
namespace App\DependencyInjection;

use GuzzleHttp\Client;
use App\Probe\ProbeDefinition;
use App\Probe\DeviceDefinition;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProbeStore
 * @package App\DependencyInjection
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
}