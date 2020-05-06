<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Processor;

use App\AlertDestination\AlertDestinationFactory;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Storage\Cache;
use App\Storage\StorageFactory;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class Processor
{
    protected $logger = null;
    protected $em = null;
    protected $alertDestinationFactory = null;

    protected $storage;
    protected $cache;

    public function __construct(StorageFactory $factory, AlertDestinationFactory $alertDestinationFactory, LoggerInterface $logger, EntityManagerInterface $entityManager, Cache $cache)
    {
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->alertDestinationFactory = $alertDestinationFactory;

        $this->cache = $cache;

        $this->storage = $factory->create();
    }

    private function handleAlertRules(Collection $rules, Device $device, Probe $probe, SlaveGroup $group, $timestamp, AlertRule $parent = null)
    {
        foreach ($rules as $alertRule) {
            if ($alertRule->getParent() == $parent) {
                if ($alertRule->getProbe() == $probe) {
                    $pattern   = explode(",", $alertRule->getPattern());
                    $value = $this->cache->getPatternValues($device, $alertRule, $group);
                    if ($this->matchPattern($pattern, $value)) {
                        $alert = $this->em->getRepository("App:Alert")->findOneBy(array(
                            'device' => $device,
                            'alertRule' => $alertRule,
                            'slaveGroup' => $group,
                            'active' => 1
                        ));
                        if (!$alert) {
                            $alert = new Alert();
                            $alert->setDevice($device);
                            $alert->setAlertRule($alertRule);
                            $alert->setSlaveGroup($group);
                            $alert->setActive(1);
                            $alert->setFirstseen(new \DateTime());
                            $destinations = $device->getActiveAlertDestinations();
                            foreach ($destinations as $destination) {
                                $this->alertDestinationFactory->create($destination)->trigger($alert);
                            }
                        }
                        $alert->setLastseen(new \DateTime());
                        $this->em->persist($alert); //flush will be done in slavecontroller

                    } else {
                        $alert = $this->em->getRepository("App:Alert")->findOneBy(array(
                            'device' => $device,
                            'alertRule' => $alertRule,
                            'slaveGroup' => $group,
                            'active' => 1
                        ));
                        if ($alert) {
                            $alert->setActive(0);
                            //$this->em->persist($alert); //flush will be done in slavecontroller
                            $destinations = $device->getActiveAlertDestinations();
                            foreach ($destinations as $destination) {
                                $this->alertDestinationFactory->create($destination)->clear($alert);
                            }
                            $this->em->remove($alert); //flush will be done in slavecontroller
                        }
                        $this->handleAlertRules($rules, $device, $probe, $group, $timestamp, $alertRule);
                    }
                }
            }
        }
    }

    protected function processAlertRules(Device $device, Probe $probe, SlaveGroup $group, $timestamp)
    {
        $this->handleAlertRules($device->getActiveAlertRules(), $device, $probe, $group, $timestamp);
    }

    protected function matchPattern($pattern, $value)
    {
        $value = is_array($value) ? array_values($value) : [];
        if (count($pattern) != count($value)) {
            $this->logger->warning("Number of values does not equal pattern: (".count($value)." vs ".count($pattern).")");
            return false;
        }
        $result = true;
        foreach($pattern as $key => $field) {
            switch($field[0]) {
                case "=":
                    $val = str_replace("=", "", $field);
                    if($value[$key] != $val) {
                        $result = false;
                    }
                    break;
                case ">":
                    $val = str_replace(">", "", $field);
                    if($value[$key] <= $val) {
                        $result = false;
                    }
                    break;
                case "<":
                    $val = str_replace("<", "", $field);
                    if($value[$key] >= $val) {
                        $result = false;
                    }
                    break;
            }
        }

        return $result;
    }

    protected function cacheResults(Device $device, SlaveGroup $group, $timestamp, $datasources)
    {
        foreach ($device->getActiveAlertRules() as $alertRule) {
            $pattern = explode(",", $alertRule->getPattern());
            $value = $this->cache->getPatternValues($device, $alertRule, $group);
            if (!is_array($value)) {
                $value = array();
            }
            if (!isset($datasources[$alertRule->getDatasource()])) {
                $this->logger->warning("Datasource ".$alertRule->getDatasource()." not found");
            }
            $value[$timestamp] = $datasources[$alertRule->getDatasource()];

            ksort($value);
            while(count($value) > count($pattern)) {
                reset($value);
                unset($value[key($value)]);
            }

            $this->cache->setPatternValues($device, $alertRule, $group, $value);
        }
    }

    abstract function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data);
}
