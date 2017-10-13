<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Processor;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use AppBundle\Exception\WrongTimestampRrdException;

class PingProcessor extends Processor
{
    public function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        if (count($data) != $probe->getSamples()) {
            throw new \Exception(count($data)." ping samples received, should have been ".$probe->getSamples());
        }
        $datasources = array();
        $failed = 0;
        $success = 0;

        $times = array();
        foreach ($data as $key => $result) {
            $datasources['ping'.($key+1)] = $result;
            if ($result != -1) {
                $success++;
                $times[] = $result;
            } else {
                $failed++;
            }
        }

        sort($times);
        $datasources['loss'] = $failed / $probe->getSamples();
        if ($success == 0) {
            $datasources['median'] = "U";
        } else {
            $datasources['median'] = $times[floor(count($times) / 2)];
        }

        try {
            $this->storage->store($device, $probe, $group, $timestamp, $datasources);
            $datasources['failures'] = $this->storage->fetch($device, $probe, $group, $timestamp, 'median', 'FAILURES');

            $this->cacheResults($device, $timestamp, $datasources);
            $this->processAlertRules($device, $probe, $group, $timestamp);
        } catch (WrongTimestampRrdException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}