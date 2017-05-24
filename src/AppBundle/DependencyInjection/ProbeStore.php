<?php
namespace AppBundle\DependencyInjection;

use GuzzleHttp\Client;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;

class ProbeStore
{
    protected $probes = array();

    public function __construct()
    {
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
                print("Probe " . $probe->getId() . " found. [T: " . $probe->getType() . ", St:" . $probe->getStep() . ", Sa: " . $probe->getSamples() . "]\n");
                return $probe;
            }
        }
        print("New Probe $id: [Type: $type, Step: $step, Samples: $samples]\n");
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
            print("[" . get_class($this) . "] asking $id to purge all inactive devices.\n");
            $probe->purgeAllInactiveDevices();
        }
    }

    public function sync()
    {
        print("[ProbeStore] Synchronizing Probes with Master\n");
        print("Device States at Start of Sync: \n");
        $this->printDevices();
        print("[ProbeStore] Deactivating all Devices\n");
        $this->deactivateAllDevices();
        print("Device States after Deactivation: \n");
        $this->printDevices();
        print("[ProbeStore] Fetching new config from Master\n");
        $client = new Client();
        $result = $client->get('https://smokeping-dev.cegeka.be/api/slaves/1/config');
        print("[ProbeStore] Parsing new config\n");
        $decoded = json_decode($result->getBody(), true);
        foreach ($decoded as $id => $probeConfig)
        {
            $type = $probeConfig['type'];
            $step = $probeConfig['step'];
            $samples = $probeConfig['samples'];

            print("[ProbeStore] Grabbing Probe\n");
            $probe = $this->getProbe($id, $type, $step, $samples);
            foreach ($probeConfig['targets'] as $hostname => $ip)
            {
                $device = new DeviceDefinition($hostname, $ip);
                $probe->addDevice($device);
            }
        }
        print("Device States after Parsing New Config: \n");
        $this->printDevices();
        print("[ProbeStore] Purging all inactive Devices\n");
        $this->purgeAllInactiveDevices();
        print("Device States after Purging: \n");
        $this->printDevices();
        print("[ProbeStore] Synchronization Completed.\n");
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