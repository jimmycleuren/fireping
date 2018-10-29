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
        $datasources = array();

        $prevTotal = 0;
        foreach ($data->hop as $hop => $details) {
            if (isset($details->latencies) && count($details->latencies) != $probe->getSamples()) {
                throw new \Exception(count((array)$details)." traceroute samples received for hop $hop, should have been ".$probe->getSamples());
            }
            $total = 0;
            $failed = 0;
            $success = 0;
            if (isset($details->latencies)) {
                foreach ($details->latencies as $latency) {
                    if ($latency != -1) {
                        $total += $latency;
                        $success++;
                    } else {
                        $failed++;
                    }
                }
                $name = $hop."_".str_replace(".", "_", $details->ip);

                $datasources[$name . "l"] = $failed;
                if ($success == 0) {
                    $datasources[$name . "m"] = "U";
                } else {
                    $absolute = $total / $success;
                    $delta = $absolute - $prevTotal;
                    $datasources[$name . "m"] = $absolute;
                    $prevTotal += $delta;
                }
            }
        }

        $this->storage->store($device, $probe, $group, $timestamp, $datasources, true);
        $datasources['failures'] = $this->storage->fetch($device, $probe, $group, $timestamp, 'median', 'FAILURES');

        //TODO: is this needed ?
        //$this->cacheResults($device, $timestamp, $datasources);
        //$this->processAlertRules($device, $probe, $group, $timestamp);
    }
}