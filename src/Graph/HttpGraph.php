<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;

class HttpGraph extends SmokeRrdGraph
{
    protected $datasource = "latency";

    public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $type = "default", $debug = false)
    {
        if (!$end) $end = date("U");

        if ($start < 0) {
            $start = date("U") + $start;
        }

        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        if (!$this->storage->fileExists($device, $file)) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        switch($type) {
            case "response":
                return $this->getResponseDetailGraph($device, $probe, $slavegroup, $start, $end, $debug);
            case "latency":
            default:
                return $this->getLatencyDetailGraph($device, $probe, $slavegroup, $start, $end, $debug);
        }
    }

    protected function getResponseDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $debug = false)
    {
        $options = array(
            "--slope-mode",
            "--border=0",
            "--start", $start,
            "--end", $end,
            "--title=".$device->getName(),
            "--vertical-label=samples",
            "--lower-limit=0",
            "--upper-limit=".($probe->getSamples() + 1),
            "--rigid",
            "--width=1000",
            "--height=200",
            "--color=BACK".$_ENV['RRD_BACKGROUND']
        );

        $datasources = $this->storage->getDatasources($device, $probe, $slavegroup);

        $first = true;
        $options[] = "COMMENT:HTTP status codes\\n";
        foreach ($datasources as $key => $data)
        {
            if(substr($data, 0, 4) !== "code") {
                continue;
            }

            $options[] = sprintf("DEF:%s=%s:%s:%s", $data, $this->storage->getFilePath($device, $probe, $slavegroup), $data, "AVERAGE");

            $options[] = sprintf("CDEF:%s=%s", $data."_1", "$data,100,GE,$data,199,LE,*");
            $options[] = sprintf("CDEF:%s=%s", $data."_2", "$data,200,GE,$data,299,LE,*");
            $options[] = sprintf("CDEF:%s=%s", $data."_3", "$data,300,GE,$data,399,LE,*");
            $options[] = sprintf("CDEF:%s=%s", $data."_4", "$data,400,GE,$data,499,LE,*");
            $options[] = sprintf("CDEF:%s=%s", $data."_5", "$data,500,GE,$data,599,LE,*");
        }

        $options[] = sprintf("CDEF:%s=%s", "total_1", $this->getRpn($probe->getSamples(), 1));
        $options[] = sprintf("CDEF:%s=%s", "total_2", $this->getRpn($probe->getSamples(), 2));
        $options[] = sprintf("CDEF:%s=%s", "total_3", $this->getRpn($probe->getSamples(), 3));
        $options[] = sprintf("CDEF:%s=%s", "total_4", $this->getRpn($probe->getSamples(), 4));
        $options[] = sprintf("CDEF:%s=%s", "total_5", $this->getRpn($probe->getSamples(), 5));

        foreach ($datasources as $key => $data)
        {
            if(substr($data, 0, 4) !== "code") {
                continue;
            }

            if ($first === true) {
                $options[] = "AREA:".$data."_1#" . $this->getColor(100) . ":1xx";
                $options[] = "GPRINT:total_1:AVERAGE:%5.1lf %%\\n";
                $options[] = "STACK:".$data."_2#" . $this->getColor(200) . ":2xx";
                $options[] = "GPRINT:total_2:AVERAGE:%5.1lf %%\\n";
                $options[] = "STACK:".$data."_3#" . $this->getColor(300) . ":3xx";
                $options[] = "GPRINT:total_3:AVERAGE:%5.1lf %%\\n";
                $options[] = "STACK:".$data."_4#" . $this->getColor(400) . ":4xx";
                $options[] = "GPRINT:total_4:AVERAGE:%5.1lf %%\\n";
                $options[] = "STACK:".$data."_5#" . $this->getColor(500) . ":5xx";
                $options[] = "GPRINT:total_5:AVERAGE:%5.1lf %%\\n";
            } else {
                $options[] = "STACK:".$data."_1#" . $this->getColor(100);
                $options[] = "STACK:".$data."_2#" . $this->getColor(200);
                $options[] = "STACK:".$data."_3#" . $this->getColor(300);
                $options[] = "STACK:".$data."_4#" . $this->getColor(400);
                $options[] = "STACK:".$data."_5#" . $this->getColor(500);
            }
            $first = false;
        }

        $options[] = "COMMENT:".$probe->getName()." (".$probe->getSamples()." probes of type ".$probe->getType()." in ".$probe->getStep()." seconds) from ".$slavegroup->getName()."";
        $options[] = "COMMENT:ending on ".date("D M j H\\\:i\\\:s Y", $end)."";

        return $this->storage->graph($device, $options);
    }

    protected function getRpn($samples, $response)
    {
        $rpn = "";
        for ($i = 1; $i <= $samples; $i++) {
            $rpn .= "code".$i."_".$response.",";
        }
        for ($i = 1; $i < $samples - 1; $i++) {
            $rpn .= "+,";
        }

        $multiplier = 100 / $samples;
        return $rpn."+,".$multiplier.",*";
    }

    protected function getLatencyDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $debug = false)
    {
        return parent::getDetailGraph($device, $probe, $slavegroup, $start, $end);
    }

    public function getColor($code)
    {
        switch(floor($code / 100)) {
            case 1: return "999999";
            case 2: return "00ff00";
            case 3: return "0000ff";
            case 4: return "ff0000";
            case 5: return "ff00ff";
            default: return "333333";
        }
    }
}