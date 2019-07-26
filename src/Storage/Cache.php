<?php

namespace App\Storage;

use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class Cache
{
    private $cache;

    public function __construct()
    {
        $connection = RedisAdapter::createConnection("redis://localhost");
        $this->cache = new RedisAdapter($connection, 'fireping', 3600 * 24);
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $key, $value)
    {
        $key = $device->getId()."-".$probe->getId()."-".$group->getId()."-".$key;
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->expiresAfter($probe->getStep() + 10);
        $cacheItem->set($value);
        $this->cache->save($cacheItem);
    }

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $key)
    {
        $key = $device->getId()."-".$probe->getId()."-".$group->getId()."-".$key;
        $cacheItem = $this->cache->getItem($key);
        $value = $cacheItem->get();
        return $value;
    }

    public function getPatternValues(Device $device, AlertRule $alertRule, SlaveGroup $group)
    {
        $key       = $this->getPatternKey($device, $alertRule, $group);
        $cacheItem = $this->cache->getItem($key);
        $value     = $cacheItem->get();

        return $value;
    }

    public function setPatternValues(Device $device, AlertRule $alertRule, SlaveGroup $group, $value)
    {
        $key       = $this->getPatternKey($device, $alertRule, $group);
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set($value);
        $this->cache->save($cacheItem);
    }

    protected function getPatternKey(Device $device, AlertRule $alertRule, SlaveGroup $group)
    {
        return "pattern.".$device->getId().".".$alertRule->getProbe()->getId().".".$alertRule->getId().".".$group->getId();
    }
}