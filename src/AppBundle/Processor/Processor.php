<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Processor;

use AppBundle\Entity\Alert;
use AppBundle\Entity\AlertRule;
use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Cache\Adapter\RedisAdapter;

abstract class Processor
{
    protected $logger = null;
    protected $em = null;

    protected $storage;
    protected $container;
    protected $cache;

    public function __construct($container)
    {
        $this->container = $container;
        $this->storage = $container->get('storage.'.$container->getParameter('storage'));
        $this->logger = $container->get('logger');
        $this->em = $this->container->get('doctrine')->getManager();

        $connection = RedisAdapter::createConnection("redis://localhost");
        $this->cache = new RedisAdapter($connection, 'fireping', 3600 * 24);
    }

    private function handleAlertRules(Collection $rules, Device $device, Probe $probe, SlaveGroup $group, $timestamp, AlertRule $parent = null)
    {
        foreach ($rules as $alertRule) {
            if ($alertRule->getParent() == $parent) {
                if ($alertRule->getProbe() == $probe) {
                    $pattern   = explode(",", $alertRule->getPattern());
                    $key       = $this->getCacheKey($device, $alertRule, $group);
                    $cacheItem = $this->cache->getItem($key);
                    $value     = $cacheItem->get();
                    if ($this->matchPattern($pattern, $value)) {
                        $alert = $this->em->getRepository("AppBundle:Alert")->findOneBy(array(
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
                            $this->container->get("monolog.logger.alert")->info("ALERT: " . $alertRule->getName() . " on $device from $group");
                        }
                        $alert->setLastseen(new \DateTime());
                        $this->em->persist($alert); //flush will be done in slavecontroller

                    } else {
                        $alert = $this->em->getRepository("AppBundle:Alert")->findOneBy(array(
                            'device' => $device,
                            'alertRule' => $alertRule,
                            'slaveGroup' => $group,
                            'active' => 1
                        ));
                        if ($alert) {
                            $alert->setActive(0);
                            $this->em->persist($alert); //flush will be done in slavecontroller
                            $this->container->get("monolog.logger.alert")->info("CLEAR: " . $alertRule->getName() . " on $device from $group");
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
        $value = array_values($value);
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
            $key = $this->getCacheKey($device, $alertRule, $group);
            $cacheItem = $this->cache->getItem($key);
            $value = $cacheItem->get();
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

            $cacheItem->set($value);
            $this->cache->save($cacheItem);
        }
    }

    protected function getCacheKey(Device $device, AlertRule $alertRule, SlaveGroup $group)
    {
        return "pattern.".$device->getId().".".$alertRule->getProbe()->getId().".".$alertRule->getId().".".$group->getId();
    }

    abstract function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data);
}