<?php

namespace App\Tests\App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\SlaveGroup;
use App\Storage\RrdCachedStorage;
use App\Storage\RrdStorage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RrdStorageTest extends TestCase
{
    public function testFetch()
    {
        @unlink("/tmp/1/1/1.rrd");

        $storage = new RrdStorage("/tmp/", new NullLogger());

        $timestamp = date("U") - date("U") % 60;

        $device = new Device();
        $device->setId(1);

        $archive = new ProbeArchive();
        $archive->setFunction('AVERAGE');
        $archive->setRows(100);
        $archive->setSteps(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setStep(60);
        $probe->setSamples(1);
        $probe->addArchive($archive);

        $group = new SlaveGroup();
        $group->setId(1);

        $storage->store($device, $probe, $group, $timestamp, ['median' => 10]);
        $storage->store($device, $probe, $group, $timestamp + 60, ['median' => 10]);

        $this->assertEquals(10, $storage->fetch($device, $probe, $group, $timestamp + 60, "median", "AVERAGE"));
    }

    public function testFetchUnknown()
    {
        @unlink("/tmp/1/1/1.rrd");

        $storage = new RrdStorage("/tmp/", new NullLogger());

        $timestamp = date("U") - date("U") % 60;

        $device = new Device();
        $device->setId(1);

        $archive = new ProbeArchive();
        $archive->setFunction('AVERAGE');
        $archive->setRows(100);
        $archive->setSteps(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setStep(60);
        $probe->setSamples(1);
        $probe->addArchive($archive);

        $group = new SlaveGroup();
        $group->setId(1);

        $storage->store($device, $probe, $group, $timestamp, ['median' => 10]);
        $storage->store($device, $probe, $group, $timestamp + 180, ['median' => 10]);

        $this->assertEquals('U', $storage->fetch($device, $probe, $group, $timestamp + 60, "median", "AVERAGE"));
    }
}