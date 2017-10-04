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

class PingProcessor extends Processor
{
    public function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        if (count($data) != $probe->getSamples()) {
            throw new \Exception(count($data)." ping samples received, should have been ".$probe->getSamples());
        }
        $datasources = array();
        $total = 0;
        $failed = 0;
        $success = 0;

        foreach ($data as $key => $result) {
            $datasources['ping'.($key+1)] = $result;
            if ($result != -1) {
                $total += $result;
                $success++;
            } else {
                $failed++;
            }
        }

        $datasources['loss'] = $failed / $probe->getSamples();
        if ($success == 0) {
            $datasources['median'] = "U";
        } else {
            $datasources['median'] = $total / $success;
        }

        $this->storage->store($device, $probe, $group, $timestamp, $datasources);

        $this->cacheResults($device, $timestamp, $datasources);
        $this->processAlertRules($device, $probe, $group, $timestamp);
    }

    private function processAlertRules(Device $device, Probe $probe, SlaveGroup $group, $timestamp)
    {
        foreach ($device->getAllAlertRules() as $alertRule) {
            if ($alertRule->getProbe() == $probe) {
                $pattern = explode(",", $alertRule->getPattern());
                $key = $this->getCacheKey($device, $alertRule);
                $cacheItem = $this->cache->getItem($key);
                $value = $cacheItem->get();
                if ($this->matchPattern($pattern, $value)) {
                    $this->container->get("monolog.logger.alert")->info("ALERT: ".$alertRule->getName()." on $device");
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
                    }
                    $alert->setLastseen(new \DateTime());
                    $this->em->persist($alert);
                    $this->em->flush();
                } else {
                    $alert = $this->em->getRepository("AppBundle:Alert")->findOneBy(array(
                        'device' => $device,
                        'alertRule' => $alertRule,
                        'slaveGroup' => $group,
                        'active' => 1
                    ));
                    if ($alert) {
                        $alert->setActive(0);
                        $this->em->persist($alert);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    private function matchPattern($pattern, $value)
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

    private function cacheResults(Device $device, $timestamp, $datasources)
    {
        foreach ($device->getAllAlertRules() as $alertRule) {
            $pattern = explode(",", $alertRule->getPattern());
            $key = $this->getCacheKey($device, $alertRule);
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

            //$this->logger->info("Writing $key: ".count($value)." ".count($pattern).": ".print_r($value, true));
            $cacheItem->set($value);
            $this->cache->save($cacheItem);
        }
    }

    private function getCacheKey(Device $device, AlertRule $alertRule)
    {
        return "pattern.".$device->getId().".".$alertRule->getProbe()->getId().".".$alertRule->getId();
    }
}