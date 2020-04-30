<?php

namespace App\Graph;

use App\Entity\Slave;
use App\Storage\SlaveStatsRrdStorage;

class SlaveGraph
{
    private $storage;

    public function __construct(SlaveStatsRrdStorage $storage)
    {
        $this->storage = $storage;
    }

    public function getGraph(Slave $slave, $type, $start = -3600, $end = null, $debug = false)
    {
        if (!$end) $end = date("U");

        $file = $this->storage->getFilePath($slave, $type);
        if (!file_exists($file)) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        $max = 100000;

        if ($start < 0) {
            $start = date("U") + $start;
        }
        $title = $slave->getId() . " - $type";

        $options = array(
            "--slope-mode",
            "--border=0",
            "--start", $start,
            "--end", $end,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            //"--upper-limit=".$this->getMax($slave, $start, $end, $this->storage->getFilePath($slave, $type)),
            "--rigid",
            "--width=1000",
            "--height=200",
        );

        switch ($type) {
            case 'posts':
                $options = $this->createPostGraph($slave, $options);
                break;
            case 'workers':
                $options = $this->createWorkersGraph($slave, $file, $options);
                break;
            case 'queues':
                $options = $this->createQueuesGraph($slave, $file, $options);
                break;
        }
        /*
        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe, $slavegroup), 'loss', "AVERAGE");

        $options[] = "CDEF:dm0=median,0,$max,LIMIT";
        $options[] = sprintf("CDEF:%s=%s,%s,%s,%s,%s",'loss_percent', "loss", $probe->getSamples(), "/", "100", "*");
        $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe, $slavegroup), $probe->getSamples(), $slavegroup);
        $options[] = "CDEF:s2d0=".$slavegroup->getId()."sdev0";


        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $options[] = "DEF:".$this->datasource."$i=$file:".$this->datasource."$i:AVERAGE";
            $options[] = "CDEF:cp$i=".$this->datasource."$i,$max,LT,".$this->datasource."$i,INF,IF";
        }
        $half = $probe->getSamples() / 2;
        $itop = $probe->getSamples();
        $ibot = 1;
        for (; $itop > $ibot; $itop--, $ibot++) {
            $color = (int)((190/$half) * ($half-$ibot))+50;
            $options[] = "CDEF:smoke$ibot=cp$ibot,UN,UNKN,cp$itop,cp$ibot,-,IF";
            $options[] = "AREA:cp$ibot";
            $options[] = "STACK:smoke$ibot#".sprintf("%02x", $color).sprintf("%02x", $color).sprintf("%02x", $color);
        }

        if ($debug) {
            $options[] = sprintf("LINE1:%s%s", 'upper', '#ff0000');
            $options[] = sprintf("LINE1:%s%s", 'lower', '#0000ff');
            $options[] = sprintf("LINE1:%s%s", 'hwpredict', '#ff00ff');
        }

        $options[] = "GPRINT:median:AVERAGE:median rtt\: %6.1lf ms avg";
        $options[] = "GPRINT:median:MAX:%7.1lf ms max";
        $options[] = "GPRINT:median:MIN:%7.1lf ms min";
        $options[] = "GPRINT:median:LAST:%7.1lf ms now";
        $options[] = "GPRINT:s2d0:AVERAGE:%7.1lf ms sd";
        $options[] = "COMMENT: \\n";

        $options[] = "GPRINT:loss_percent:AVERAGE:packet loss\: %6.2lf %% avg";
        $options[] = "GPRINT:loss_percent:MAX:%8.2lf %% max";
        $options[] = "GPRINT:loss_percent:MIN:%8.2lf %% min";
        $options[] = "GPRINT:loss_percent:LAST:%8.2lf %% now";
        $options[] = "COMMENT: \\n";
        $options[] = "COMMENT:loss color\:  ";

        $swidth = $this->getMedianMax($device, $start, $end, $this->storage->getFilePath($device, $probe, $slavegroup)) / 200;
        $last = -1;
        foreach ($lossColors as $loss => $color) {
            $options[] = "CDEF:me$loss=loss,$last,GT,loss,$loss,LE,*,1,UNKN,IF,median,*";
            $options[] = "CDEF:meL$loss=me$loss,$swidth,-";
            $options[] = "CDEF:meH$loss=me$loss,0,*,$swidth,2,*,+";
            $options[] = "AREA:meL$loss";
            $options[] = "STACK:meH$loss$color[1]:$color[0]";
            $last = $loss;
        }

        */
        $options[] = "COMMENT: \\n";

        //$options[] = "COMMENT:".$probe->getName()." (".$probe->getSamples()." probes of type ".$probe->getType()." in ".$probe->getStep()." seconds) from ".$slavegroup->getName()."";
        $options[] = "COMMENT:ending on ".date("D M j H\\\:i\\\:s Y", $end)."";

        return $this->storage->graph($options);
    }

    public function createPostGraph($slave, $options)
    {
        $options[] = sprintf("DEF:%s=%s:%s:%s",'failed', $this->storage->getFilePath($slave, 'posts'), 'failed', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'discarded', $this->storage->getFilePath($slave, 'posts'), 'discarded', "AVERAGE");

        $options[] = sprintf("LINE1:%s%s:%s", 'failed', '#ff0000', "failed\t");


        $options[] = "GPRINT:failed:AVERAGE:\: %6.1lf avg";
        $options[] = "GPRINT:failed:MAX:%7.1lf max";
        $options[] = "GPRINT:failed:MIN:%7.1lf min";
        $options[] = "GPRINT:failed:LAST:%7.1lf now";
        $options[] = "COMMENT: \\n";

        $options[] = sprintf("LINE1:%s%s:%s", 'discarded', '#0000ff', "discarded\t");

        $options[] = "GPRINT:discarded:AVERAGE:\: %6.1lf avg";
        $options[] = "GPRINT:discarded:MAX:%7.1lf max";
        $options[] = "GPRINT:discarded:MIN:%7.1lf min";
        $options[] = "GPRINT:discarded:LAST:%7.1lf now";
        $options[] = "COMMENT: \\n";

        return $options;
    }

    public function createWorkersGraph($slave, $file, $options)
    {
        $options[] = sprintf("DEF:%s=%s:%s:%s",'total', $this->storage->getFilePath($slave, 'workers'), 'total', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'available', $this->storage->getFilePath($slave, 'workers'), 'available', "AVERAGE");

        //$options[] = sprintf("AREA:%s%s", 'total', '#00ff00');

        $temp = $this->storage->getDataSources($file);
        $datasources = [];
        if (in_array("ping", $temp)) {
            $datasources[] = "ping";
            unset($temp[array_search("ping", $temp)]);
        }
        if (in_array("traceroute", $temp)) {
            $datasources[] = "traceroute";
            unset($temp[array_search("traceroute", $temp)]);
        }
        $datasources = array_merge($datasources, $temp);
        $index = 0;
        foreach ($datasources as $datasource) {
            if ($datasource != "total" && $datasource != "available") {
                $options[] = sprintf("DEF:%s=%s:%s:%s",$datasource, $this->storage->getFilePath($slave, 'workers'), $datasource, "AVERAGE");
                if ($index == 0) {
                    $options[] = sprintf("AREA:%s%s:%s", $datasource, $this->getColor($index, count($this->storage->getDataSources($file))), sprintf("%-10s", $datasource));
                } else {
                    $options[] = sprintf("STACK:%s%s:%s", $datasource, $this->getColor($index, count($this->storage->getDataSources($file))), sprintf("%-10s", $datasource));
                }
                $index++;

                $options[] = "GPRINT:$datasource:AVERAGE:\: %6.1lf avg";
                $options[] = "GPRINT:$datasource:MAX:%7.1lf max";
                $options[] = "GPRINT:$datasource:MIN:%7.1lf min";
                $options[] = "COMMENT: \\n";
            }
        }
        $options[] = sprintf("STACK:%s%s:%s", 'available', '#eeeeee', sprintf("%-10s", "available"));

        $options[] = "GPRINT:available:AVERAGE:\: %6.1lf avg";
        $options[] = "GPRINT:available:MAX:%7.1lf max";
        $options[] = "GPRINT:available:MIN:%7.1lf min";
        $options[] = "COMMENT: \\n";

        /*
        $options[] = "GPRINT:total:AVERAGE:total workers     \: %6.1lf avg";
        $options[] = "GPRINT:total:MAX:%7.1lf max";
        $options[] = "GPRINT:total:MIN:%7.1lf min";
        $options[] = "GPRINT:total:LAST:%7.1lf now";
        $options[] = "COMMENT: \\n";
        */

        return $options;
    }

    public function createQueuesGraph($slave, $file, $options)
    {
        //TODO: get real amount of queues
        for ($i = 0; $i < 10; $i++) {
            $options[] = sprintf("DEF:%s=%s:%s:%s","queue$i", $this->storage->getFilePath($slave, 'queues'), "queue$i", "AVERAGE");
            $options[] = sprintf("LINE1:%s%s:%s", "queue$i", $this->getColor($i, count($this->storage->getDataSources($file))), sprintf("%-7s", "Queue $i"));

            $options[] = "GPRINT:queue$i:AVERAGE:\: %7.1lf avg";
            $options[] = "GPRINT:queue$i:MAX:%7.1lf max";
            $options[] = "GPRINT:queue$i:MIN:%7.1lf min";
            $options[] = "GPRINT:queue$i:LAST:%7.1lf now";
            $options[] = "COMMENT: \\n";
        }

        return $options;
    }

    private function getColor($id, $total)
    {
        $width = 127;
        $center = 128;
        $frequency = pi() * 2 / $total;

        $red = sin($frequency * $id + 0) * $width + $center;
        $green = sin($frequency * $id + 2) * $width + $center;
        $blue = sin($frequency * $id + 4) * $width + $center;

        return "#".sprintf("%02x", $red).sprintf("%02x", $green).sprintf("%02x", $blue);
    }

    protected function getMedianMax(Slave $slave, $type, $start, $end, $file)
    {
        $options = array(
            "--start", $start,
            "--end", $end,
            "--width=600",
            "DEF:max=$file:median:AVERAGE",
            "PRINT:max:MAX:%le"
        );

        $maxMedian = $this->storage->getGraphValue($slave, $options);

        return $maxMedian * 1.2;
    }
}