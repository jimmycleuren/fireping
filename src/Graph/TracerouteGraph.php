<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;

class TracerouteGraph extends RrdGraph
{
    public function getSummaryGraph(Device $device, Probe $probe)
    {
        return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
    }

    public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $debug = false)
    {

        if (!$end) $end = date("U");

        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        if (!$this->storage->fileExists($device, $file)) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        if ($start < 0) {
            $start = date("U") + $start;
        }
        $title = $device->getName();

        $options = array(
            "--slope-mode",
            "--border=0",
            "--start", $start,
            "--end", $end,
            "--title=$title",
            "--vertical-label=absolute ms per hop",
            "--lower-limit=0",
            "--rigid",
            "--width=1000",
            "--height=300",
        );

        $datasources = $this->storage->getDatasources($device, $probe, $slavegroup);

        $hops = [];
        foreach ($datasources as $datasource)
        {
            $name = substr($datasource, 0, -1);
            $hops[] = $name;
        }
        $hops = array_unique($hops);
        usort($hops, function($a, $b) {
            $parts1 = explode("_", $a);
            $id1 = $parts1[0];
            $parts2 = explode("_", $b);
            $id2 = $parts2[0];
            if ($id1 == $id2) {
                return 0;
            } else {
                return $id1 < $id2 ? -1 : 1;
            }
        });

        foreach ($hops as $key => $hop)
        {
            $parts = explode("_", $hop);
            $name = implode("", $parts);
            $id = array_shift($parts);
            $ip = implode(".", $parts);

            if ($this->getMedian($device, $start, $end, $this->storage->getFilePath($device, $probe, $slavegroup), $hop.'m') > 0) {
                $options[] = sprintf("DEF:%s=%s:%s:%s", $name . "median", $this->storage->getFilePath($device, $probe, $slavegroup), $hop . 'm', "AVERAGE");
                $options[] = sprintf("DEF:%s=%s:%s:%s", $name . "loss", $this->storage->getFilePath($device, $probe, $slavegroup), $hop . 'l', "AVERAGE");
                $options[] = sprintf("CDEF:%s=%s,%s,%s,%s,%s", $name . 'losspercent', $name . "loss", $probe->getSamples(), "/", "100", "*");

                if ($id == 1) {
                    $options[] = "AREA:$name" . "median#" . $this->getColor($key, count($hops)) . ":" . sprintf("%2s", $id) . sprintf("%16s", $ip);
                } else {
                    $options[] = "STACK:$name" . "median#" . $this->getColor($key, count($hops)) . ":" . sprintf("%2s", $id) . sprintf("%16s", $ip);
                }


                $options[] = "GPRINT:$name" . "median:AVERAGE:rtt\: %6.1lf ms avg";
                $options[] = "GPRINT:$name" . "median:MIN:%6.1lf ms min";
                $options[] = "GPRINT:$name" . "median:MAX:%6.1lf ms max";
                $options[] = "GPRINT:$name" . "losspercent:AVERAGE:packet loss\: %6.2lf %% avg";
                $options[] = "COMMENT: \\n";
            }
        }

        return $this->storage->graph($device, $options);
    }

    private function getColor($id, $total)
    {
        $width = 127;
        $center = 128;
        $frequency = pi() * 2 / $total;

        $red = sin($frequency * $id + 0) * $width + $center;
        $green = sin($frequency * $id + 2) * $width + $center;
        $blue = sin($frequency * $id + 4) * $width + $center;

        return sprintf("%02x", $red).sprintf("%02x", $green).sprintf("%02x", $blue);
    }

    private function getMedian(Device $device, $start, $end, $file, $ds)
    {
        $options = array(
            "--start", $start,
            "--end", $end,
            "--width=600",
            "DEF:max=$file:$ds:AVERAGE",
            "PRINT:max:MAX:%le"
        );

        $value = $this->storage->getGraphValue($device, $options);

        return $value;
    }
}