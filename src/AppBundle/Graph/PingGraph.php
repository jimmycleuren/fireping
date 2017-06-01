<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Graph;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Exception\RrdException;

class PingGraph extends Graph
{
    public function getSummaryGraph(Device $device, Probe $probe)
    {
        $start = date("U") - 3600;
        $title = $device->getName();

        $imageFile = tempnam("/tmp", 'image');
        $options = array(
            //"--slope-mode",
            "--start", $start,
            "--title=$title",
            "--vertical-label=Seconds",
            "--lower-limit=0",
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe), 'median', "AVERAGE");

        $options[] = sprintf("%s:%s%s:%s", 'LINE', 'median', '#00ff00', 'median');
        $options[] = sprintf("GPRINT:%s:%s:%s", 'median', 'LAST', "cur\:%7.2lf");
        $options[] = sprintf("GPRINT:%s:%s:%s", 'median', 'AVERAGE', "avg\:%7.2lf");
        $options[] = sprintf("GPRINT:%s:%s:%s", 'median', 'MAX', "max\:%7.2lf");
        $options[] = "COMMENT:\\n";

        $return = rrd_graph($imageFile, $options);
        $error = rrd_error();
        if (!$return || $error) {
            throw new RrdException($error);
        }

        return $imageFile;
    }

    public function storeResult(Device $device, Probe $probe, $timestamp, $data)
    {
        if (count($data) != $probe->getSamples()) {
            throw new \Exception(count($data)." ping samples received, should have been ".$probe->getSamples());
        }
        $datasources = array();
        $total = 0;
        $failed = 0;
        $success = 0;

        foreach ($data as $key => $result) {
            $datasources['ping'.($key+1)] = $result;
            if ($result != -1) {
                $total += $result;
                $success++;
            } else {
                $failed++;
            }
        }

        $datasources['loss'] = $failed / $probe->getSamples();
        $datasources['median'] = $total / $success;

        $this->storage->store($device, $probe, $timestamp, $datasources);
    }
}