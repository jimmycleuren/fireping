<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

abstract class Storage
{
    abstract public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources = false);

    abstract public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function): mixed;

    abstract public function getDatasources(Device $device, Probe $probe, SlaveGroup $group);

    abstract public function listItems(string $path);

    abstract public function remove(array $items, string $path);
}
