<?php

namespace App\Processor;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\DirtyInputException;

class TracerouteProcessor extends Processor
{
    public function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data): void
    {
        $datasources = [];

        $prevTotal = 0;
        foreach ($data->hop as $hop => $details) {
            if (!isset($details->ip)) {
                throw new DirtyInputException("No ip specified for hop $hop");
            }
            if (isset($details->latencies) && count($details->latencies) != $probe->getSamples()) {
                throw new DirtyInputException(count((array) $details)." samples received for hop $hop, should have been ".$probe->getSamples());
            }
            $total = 0;
            $failed = 0;
            $success = 0;
            if (isset($details->latencies)) {
                foreach ($details->latencies as $latency) {
                    if (-1 != $latency) {
                        $total += $latency;
                        ++$success;
                    } else {
                        ++$failed;
                    }
                }
                $name = $hop.'_'.str_replace('.', '_', $details->ip);

                $datasources[$name.'l'] = $failed;
                if (0 == $success) {
                    $datasources[$name.'m'] = 'U';
                } else {
                    $absolute = $total / $success;
                    $delta = $absolute - $prevTotal;
                    $datasources[$name.'m'] = $absolute;
                    $prevTotal += $delta;
                }
            }
        }

        if (count($datasources) > 0) {
            $this->storage->store($device, $probe, $group, $timestamp, $datasources, true);
            $datasources['failures'] = $this->storage->fetch($device, $probe, $group, $timestamp, 'median', 'FAILURES');
        }

        //TODO: is this needed ?
        //$this->cacheResults($device, $timestamp, $datasources);
        //$this->processAlertRules($device, $probe, $group, $timestamp);
    }
}
