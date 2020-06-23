<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

abstract class Storage
{
    abstract function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources = false);

    abstract function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function);

    abstract function getDatasources(Device $device, Probe $probe, SlaveGroup $group);

    abstract function listItems(string $path);

    abstract function remove(array $items, string $path);

}