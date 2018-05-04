<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Processor;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

class TracerouteProcessor extends Processor
{
    public function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        if (count($data) != $probe->getSamples()) {
            throw new \Exception(count($data)." traceroute samples received, should have been ".$probe->getSamples());
        }
        $datasources = array();
        $total = 0;
        $failed = 0;
        $success = 0;

        $this->logger->info(print_r($data, true));

        /*
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
        $datasources['failures'] = $this->storage->fetch($device, $probe, $group, $timestamp, 'median', 'FAILURES');

        $this->cacheResults($device, $timestamp, $datasources);
        $this->processAlertRules($device, $probe, $group, $timestamp);
        */
    }
}