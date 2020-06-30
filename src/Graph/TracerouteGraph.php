<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

class TracerouteGraph extends RrdGraph
{
    public function getSummaryGraph(Device $device, Probe $probe, $start = -43200, $end = null, $width = 600)
    {
        return file_get_contents(dirname(__FILE__).'/../../public/notfound.png');
    }

    public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $type = "default", $debug = false)
    {
        if (!$end) {
            $end = date('U');
        }

        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        if (!$this->storage->fileExists($device, $file)) {
            return file_get_contents(dirname(__FILE__).'/../../public/notfound.png');
        }

        if ($start < 0) {
            $start = date('U') + $start;
        }
        $title = $device->getName();

        $options = [
            '--slope-mode',
            '--border=0',
            '--start', $start,
            '--end', $end,
            "--title=$title",
            '--vertical-label=absolute ms per hop',
            '--lower-limit=0',
            '--rigid',
            '--width=1000',
            '--height=300',
            '--color=BACK'.$_ENV['RRD_BACKGROUND'],
        ];

        $datasources = $this->storage->getDatasources($device, $probe, $slavegroup);

        $hops = [];
        if (is_array($datasources)) {
            foreach ($datasources as $datasource) {
                $name = substr($datasource, 0, -1);
                $hops[] = $name;
            }
            $hops = array_unique($hops);
        }

        $originalKeys = [];
        $counter = 0;
        foreach ($hops as $hop) {
            $originalKeys[$hop] = $counter++;
        }

        usort($hops, function ($a, $b) {
            $parts1 = explode('_', $a);
            $id1 = $parts1[0];
            $parts2 = explode('_', $b);
            $id2 = $parts2[0];
            if ($id1 == $id2) {
                return 0;
            } else {
                return $id1 < $id2 ? -1 : 1;
            }
        });

        $someData = false;
        $first = true;
        foreach ($hops as $key => $hop) {
            $parts = explode('_', $hop);
            $name = implode('', $parts);
            $id = array_shift($parts);
            $ip = implode('.', $parts);

            if ($this->getMedian($device, $start, $end, $this->storage->getFilePath($device, $probe, $slavegroup), $hop.'m') > 0) {
                $someData = true;
                $options[] = sprintf('DEF:%s=%s:%s:%s', $name.'median', $this->storage->getFilePath($device, $probe, $slavegroup), $hop.'m', 'AVERAGE');
                $options[] = sprintf('DEF:%s=%s:%s:%s', $name.'loss', $this->storage->getFilePath($device, $probe, $slavegroup), $hop.'l', 'AVERAGE');
                $options[] = sprintf('CDEF:%s=%s,%s,%s,%s,%s', $name.'losspercent', $name.'loss', $probe->getSamples(), '/', '100', '*');

                if (true === $first) {
                    $options[] = "AREA:$name".'median#'.$this->getColor($originalKeys[$hop], count($hops)).':'.sprintf('%2s', $id).sprintf('%16s', $ip);
                } else {
                    $options[] = "STACK:$name".'median#'.$this->getColor($originalKeys[$hop], count($hops)).':'.sprintf('%2s', $id).sprintf('%16s', $ip);
                }
                $first = false;

                $options[] = "GPRINT:$name"."median:AVERAGE:median rtt\: %7.1lf ms avg";
                $options[] = "GPRINT:$name".'median:MIN:%7.1lf ms min';
                $options[] = "GPRINT:$name".'median:MAX:%7.1lf ms max';
                $options[] = "GPRINT:$name"."losspercent:AVERAGE:packet loss\: %8.2lf %% avg";
                $options[] = "GPRINT:$name".'losspercent:MIN:%8.2lf %% min';
                $options[] = "GPRINT:$name".'losspercent:MAX:%8.2lf %% max';
                $options[] = 'COMMENT: \\n';
            }
        }

        if (!$someData) {
            $options[] = 'HRULE:0#000000';
            $options[] = 'COMMENT:No traceroute data found';
        }

        $options[] = 'COMMENT: \\n';

        $options[] = 'COMMENT:'.$probe->getName().' ('.$probe->getSamples().' probes of type '.$probe->getType().' in '.$probe->getStep().' seconds) from '.$slavegroup->getName().'';
        $options[] = 'COMMENT:ending on '.date("D M j H\\\:i\\\:s Y", $end).'';

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

        return sprintf('%02x', $red).sprintf('%02x', $green).sprintf('%02x', $blue);
    }

    private function getMedian(Device $device, $start, $end, $file, $ds)
    {
        $options = [
            '--start', $start,
            '--end', $end,
            '--width=600',
            "DEF:max=$file:$ds:AVERAGE",
            'PRINT:max:MAX:%le',
        ];

        $value = $this->storage->getGraphValue($device, $options);

        return $value;
    }
}
