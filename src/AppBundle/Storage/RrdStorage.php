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
use AppBundle\Exception\RrdException;
use AppBundle\Exception\WrongTimestampRrdException;

class RrdStorage extends Storage
{
    private $path;
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

    public function __construct($container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->path = $container->get('kernel')->getRootDir()."/../var/rrd/";

        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    public function getFilePath(Device $device, Probe $probe)
    {
        $path = $this->path.$device->getId();

        if (!file_exists($path)) {
            mkdir($path);
        }

        return $this->path.$device->getId()."/".$probe->getId().'.rrd';
    }

    public function store(Device $device, Probe $probe, $timestamp, $data)
    {
        $path = $this->getFilePath($device, $probe);

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
            throw new WrongTimestampRrdException("RRD last update was ".$update["last_update"].", cannot update at ".$timestamp);
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
}