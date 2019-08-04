<?php

namespace App\Processor;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\WrongTimestampRrdException;

class SmokeProcessor extends Processor
{
    protected $datasource = "unknown";

    public function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        if (count($data) != $probe->getSamples()) {
            throw new \Exception(count($data)." ".$this->datasource." samples received, should have been ".$probe->getSamples());
        }

        $datasources = array();
        $failed = 0;
        $success = 0;

        $times = array();
        foreach ($data as $key => $result) {
            if ($result != -1) {
                $success++;
                $times[] = $result;
            } else {
                $failed++;
            }
        }
        sort($times);

        $lowerLoss = floor($failed / 2);
        $upperLoss = $failed - $lowerLoss;

        $datasourceCounter = 1;
        for ($i = 0; $i < $lowerLoss; $i++) {
            $datasources[$this->datasource.$datasourceCounter++] = "U";
        }
        foreach ($times as $time) {
            $datasources[$this->datasource.$datasourceCounter++] = $time;
        }
        for ($i = 0; $i < $upperLoss; $i++) {
            $datasources[$this->datasource.$datasourceCounter++] = "U";
        }

        $datasources['loss'] = $failed;
        if ($success == 0) {
            $datasources['median'] = "U";
        } else {
            $datasources['median'] = $times[floor(count($times) / 2)];
        }

        $this->cache->store($device, $probe, $group, 'median', $datasources['median']);
        $this->cache->store($device, $probe, $group, 'loss', $datasources['loss']);

        $this->storage->store($device, $probe, $group, $timestamp, $datasources);
        $datasources['failures'] = $this->storage->fetch($device, $probe, $group, $timestamp, 'median', 'FAILURES');

        $this->cacheResults($device, $group, $timestamp, $datasources);
        $this->processAlertRules($device, $probe, $group, $timestamp);
    }
}