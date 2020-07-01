<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

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
        $datasources = array_filter($datasources, function($value) {
            return substr($value, 0, 4) === "code";
        });

        $codes = [];
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $codes = array_merge($codes, $this->storage->fetchAll($device, $probe, $slavegroup, $start, $end, "code$i", "LAST"));
        }
        $codes = array_diff(array_unique($codes), ["U"]);
        sort($codes);

        //TODO: find out why rrd is creating floating values and remove this dirty hack
        foreach($codes as $key => $code) {
            $codes[$key] = floor($code);
        }
        $codes = array_diff(array_unique($codes), ["U"]);
        //end dirty hack

        if(count($codes) == 0) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        $options[] = "COMMENT:HTTP status codes\\n";
        foreach ($datasources as $key => $data)
        {
            $options[] = sprintf("DEF:%s=%s:%s:%s", $data, $this->storage->getFilePath($device, $probe, $slavegroup), $data, "AVERAGE");

            foreach ($codes as $code) {
                $options[] = sprintf("CDEF:%s=%s", $data."_".$code, "$data,$code,EQ");
            }
        }

        foreach ($codes as $code) {
            $options[] = sprintf("CDEF:%s=%s", "total_".$code, $this->getRpn($probe->getSamples(), $code));
        }

        $firstDraw = true;
        $firstLegend = true;
        foreach ($datasources as $key => $data)
        {
            foreach ($codes as $code) {
                if ($firstDraw) {
                    $options[] = "AREA:" . $data . "_$code#" . $this->getColor($code, $codes) . ($firstLegend ? ":HTTP/$code" : "");
                } else {
                    $options[] = "STACK:" . $data . "_$code#" . $this->getColor($code, $codes) . ($firstLegend ? ":HTTP/$code" : "");
                }
                if ($firstLegend) {
                    $options[] = "GPRINT:total_$code:AVERAGE:%5.1lf %%\\n";
                }
                $firstDraw = false;
            }
            $firstLegend = false;
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

    public function getColor($code, $codes)
    {
        $total = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $categories = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];

        foreach ($codes as $temp) {
            $total[floor($temp / 100)]++;
            $categories[floor($temp / 100)][] = $temp;
        }
        $categories[2] = array_unique($categories[2]);
        $categories[3] = array_unique($categories[3]);
        $categories[4] = array_unique($categories[4]);
        $categories[5] = array_unique($categories[5]);

        sort($categories[2]);
        sort($categories[3]);
        sort($categories[4]);
        sort($categories[5]);

        if ($total[floor($code / 100)] == 1) {
            $step = 150;
        } else {
            $step = floor(150 / ($total[floor($code / 100)] - 1));
        }

        switch(floor($code / 100)) {
            //case 1: return "999999";
            case 2: return "00".sprintf("%02x", 105 + ($step * array_search($code, $categories[floor($code / 100)])))."00";
            case 3: return "0000".sprintf("%02x", 105 + ($step * array_search($code, $categories[floor($code / 100)])));
            case 4: return sprintf("%02x", 105 + ($step * array_search($code, $categories[floor($code / 100)])))."0000";
            case 5: return sprintf("%02x", 105 + ($step * array_search($code, $categories[floor($code / 100)])))."00".sprintf("%02x", 105 + ($step * array_search($code, $categories[floor($code / 100)])));
            default: return "333333";
        }
    }
}
