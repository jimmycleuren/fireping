<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use Psr\Container\ContainerInterface;

abstract class Storage
{
    abstract function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data);

    abstract function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function);
}