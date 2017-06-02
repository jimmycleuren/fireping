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
        $start = date("U") - 3600 * 12;
        $title = $device->getName();

        $imageFile = tempnam("/tmp", 'image');
        $options = array(
            //"--slope-mode",
            "--start", $start,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            "--width=600",
            "--height=60",
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe), 'loss', "AVERAGE");
        $options[] = sprintf("VDEF:%s=%s,%s",'stdev', "median", "STDEV");
        $options[] = sprintf("CDEF:%s=%s,%s,%s",'loss_percent', "loss", "100", "*");

        $options[] = sprintf("%s:%s%s:%s", 'LINE', 'median', '#0000ff', 'median');
        $options[] = sprintf("GPRINT:%s:%s:%s", 'median', 'AVERAGE', "%7.2lf ms av md");
        $options[] = sprintf("GPRINT:%s:%s:%s", 'loss_percent', 'AVERAGE', "%7.2lf %% av ls");
        $options[] = sprintf("GPRINT:%s:%s", 'stdev', "%7.2lf ms av sd");
        $options[] = "COMMENT: \\n";
        $options[] = "COMMENT:".date("D M j H\\\:i\\\:s Y")." \\r";

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