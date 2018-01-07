<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Storage;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use AppBundle\Exception\RrdException;
use AppBundle\Exception\WrongTimestampRrdException;

class RrdStorage extends Storage
{
    private $logger = null;
    private $path = null;
    private $archives = array(
        array(
            'function' => 'AVERAGE',
            'steps' => 1,
            'rows' => 1008
        ),
        array(
            'function' => 'AVERAGE',
            'steps' => 12,
            'rows' => 4320
        ),
        array(
            'function' => 'MIN',
            'steps' => 12,
            'rows' => 4320
        ),
        array(
            'function' => 'MAX',
            'steps' => 12,
            'rows' => 4320
        )
    );

    private $predictions = array(
        array(
            'function' => 'HWPREDICT',
            'rows' => 51840,
            'alpha' => 0.1,
            'beta' => 0.0035,
            'period' => 1440
        ),
    );

    public function __construct($container)
    {
        parent::__construct($container);

        $this->logger = $container->get('logger');
        $this->path = $container->get('kernel')->getRootDir()."/../var/rrd/";

        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    public function getFilePath(Device $device, Probe $probe, SlaveGroup $group)
    {
        $path = $this->path.$device->getId();

        if (!file_exists($path)) {
            mkdir($path);
        }

        $path = $this->path.$device->getId()."/".$probe->getId();

        if (!file_exists($path)) {
            mkdir($path);
        }

        return $this->path.$device->getId()."/".$probe->getId()."/".$group->getId().'.rrd';
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        $path = $this->getFilePath($device, $probe, $group);

        if (!file_exists($path)) {
            $this->create($path, $probe, $timestamp, $data);
        }
        $this->update($path, $probe, $timestamp, $data);
    }

    private function create($filename, $probe, $timestamp, $data)
    {
        $start = $timestamp - 1;

        $options = array(
            "--start", $start,
            "--step", $probe->getStep()
        );
        foreach ($data as $key => $value) {
            $options[] = sprintf(
                "DS:%s:%s:%s:%s:%s",
                $key,
                'GAUGE',
                $probe->getStep() * 2,
                0,
                "U"
            );
        }

        foreach ($this->archives as $value) {
            $options[] = sprintf(
                "RRA:%s:0.5:%s:%s",
                strtoupper($value['function']),
                $value['steps'],
                $value['rows']
            );
        }

        foreach ($this->predictions as $value) {
            $options[] = sprintf(
                "RRA:%s:%s:%s:%s:%s",
                strtoupper($value['function']),
                $value['rows'],
                $value['alpha'],
                $value['beta'],
                $value['period']
            );
        }


        $return = rrd_create($filename, $options);
        if (!$return) {
            throw new RrdException(rrd_error());
        }
    }

    private function update($filename, $probe, $timestamp, $data)
    {
        $info = rrd_info($filename);
        $update = rrd_lastupdate($filename);

        if ($info['step'] != $probe->getStep()) {
            throw new RrdException("Steps are not equal, ".$probe->getStep()." is configured, RRD file is using ".$info['step']);
        }

        if ($update["last_update"] >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$update["last_update"].", cannot update at ".$timestamp);
        }

        $template = array();
        $values = array($timestamp);

        foreach($data as $key => $value) {
            $template[] = $key;
            $values[] = $value;
        }
        $options = array("-t", implode(":", $template), implode(":", $values));

        $return = rrd_update($filename, $options);

        $this->logger->debug("Updating $filename with ".print_r($options, true));

        if (!$return) {
            throw new RrdException(rrd_error());
        }
    }

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function)
    {
        $path = $this->getFilePath($device, $probe, $group);

        $result = rrd_fetch($path, array($function, "--start", $timestamp - $probe->getStep()));

        return reset($result['data'][$key]);
    }
}