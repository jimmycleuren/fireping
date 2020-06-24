<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;

class SmokeRrdGraph extends RrdGraph
{
    protected $datasource = "unknown";

    public function getSummaryGraph(Device $device, Probe $probe, $start = -43200, $end = null, $width = 600)
    {
        $slavegroups = $device->getActiveSlaveGroups();

        if (!$end) $end = date("U");
        $title = $device->getName();

        $options = array(
            //"--slope-mode",
            "--border=0",
            "--start", $start,
            "--end", $end,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            "--width=".$width,
            "--height=60",
            "--color=BACK".$_ENV['RRD_BACKGROUND']
        );

        $counter = 0;
        foreach ($slavegroups as $slavegroup) {

            $file = $this->storage->getFilePath($device, $probe, $slavegroup);
            if (!$this->storage->fileExists($device, $file)) {
                continue;
            }

            $options[] = sprintf("DEF:%s=%s:%s:%s", $slavegroup->getId() . 'median', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "AVERAGE");
            $options[] = sprintf("DEF:%s=%s:%s:%s", $slavegroup->getId() . 'loss', $this->storage->getFilePath($device, $probe, $slavegroup), 'loss', "AVERAGE");
            $options[] = "CDEF:" . $slavegroup->getId() . "dm0=" . $slavegroup->getId()."median,0,100000,LIMIT";
            $options[] = sprintf("CDEF:%s=%s,%s,%s,%s,%s", $slavegroup->getId() . 'loss_percent', $slavegroup->getId() . "loss", $probe->getSamples(), "/", "100", "*");
            $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe, $slavegroup), $probe->getSamples(), $slavegroup);

            $options[] = "CDEF:" . $slavegroup->getId() . "dmlow0=" . $slavegroup->getId() . "dm0," . $slavegroup->getId() . "sdev0,2,/,-";
            $options[] = "CDEF:" . $slavegroup->getId() . "s2d0=" . $slavegroup->getId() . "sdev0";
            $options[] = sprintf("LINE:%s%s:%s", $slavegroup->getId()."median", $this->colors[$counter % 3]['main'], sprintf("%-15s", $slavegroup->getName()));
            $options[] = sprintf("AREA:%s", $slavegroup->getId() . 'dmlow0');
            $options[] = "AREA:" . $slavegroup->getId() . "s2d0".$this->colors[$counter % 3]['stddev']."::STACK";

            $options[] = "VDEF:" . $slavegroup->getId() . "avsd0=" . $slavegroup->getId() . "sdev0,AVERAGE";
            $options[] = sprintf("GPRINT:%s:%s:%s", $slavegroup->getId()."median", 'AVERAGE', "%7.2lf ms av md");
            $options[] = sprintf("GPRINT:%s:%s:%s", $slavegroup->getId() .'loss_percent', 'AVERAGE', "%7.2lf %% av ls");
            $options[] = sprintf("GPRINT:%s:%s", $slavegroup->getId() .'avsd0', "%7.2lf ms av sd");
            $options[] = "COMMENT: \\n";

            $counter++;
        }

        if ($counter == 0) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        $options[] = "COMMENT:".date("D M j H\\\:i\\\:s Y")." \\r";

        return $this->storage->graph($device, $options);
    }

    public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $type = "default", $debug = false)
    {
        if (!$end) $end = date("U");

        $lossColors = array(
            0 => array('0', '#26ff00'),
            1 => array("1/".$probe->getSamples(), '#00b8ff'),
            2 => array("2/".$probe->getSamples(), '#0059ff'),
            3 => array("3/".$probe->getSamples(), '#5e00ff'),
            4 => array("4/".$probe->getSamples(), '#7e00ff'),
            floor($probe->getSamples() / 2) => array(floor($probe->getSamples() / 2)."/".$probe->getSamples(), '#dd00ff'),
            $probe->getSamples() - 1 => array(($probe->getSamples() - 1)."/".$probe->getSamples(), '#ff0000')
        );

        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        if (!$this->storage->fileExists($device, $file)) {
            return file_get_contents(dirname(__FILE__)."/../../public/notfound.png");
        }

        $max = 100000;

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
            "--vertical-label=ms",
            "--lower-limit=0",
            "--upper-limit=".$this->getMedianMax($device, $start, $end, $this->storage->getFilePath($device, $probe, $slavegroup)),
            "--rigid",
            "--width=1000",
            "--height=200",
            "--color=BACK".$_ENV['RRD_BACKGROUND']
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe, $slavegroup), 'loss', "AVERAGE");

        if ($debug) {
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'hwpredict', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "HWPREDICT");
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'devpredict', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "DEVPREDICT");
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'failures', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "FAILURES");
        }

        $options[] = "CDEF:dm0=median,0,$max,LIMIT";
        $options[] = sprintf("CDEF:%s=%s,%s,%s,%s,%s",'loss_percent', "loss", $probe->getSamples(), "/", "100", "*");
        $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe, $slavegroup), $probe->getSamples(), $slavegroup);
        $options[] = "CDEF:s2d0=".$slavegroup->getId()."sdev0";

        if ($debug) {
            $options[] = "CDEF:upper=hwpredict,devpredict,2,*,+";
            $options[] = "CDEF:lower=hwpredict,devpredict,2,*,-";
        }

        if ($debug) {
            $options[] = sprintf("TICK:%s%s:%s", 'failures', '#fdd017', '1.0');
        }

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

        $options[] = "COMMENT: \\n";

        $options[] = "COMMENT:".$probe->getName()." (".$probe->getSamples()." probes of type ".$probe->getType()." in ".$probe->getStep()." seconds) from ".$slavegroup->getName()."";
        $options[] = "COMMENT:ending on ".date("D M j H\\\:i\\\:s Y", $end)."";

        return $this->storage->graph($device, $options);
    }

    protected function calculateStdDev(&$options, $file, $samples, $slavegroup)
    {
        $temp = array();
        $temp2 = array();
        $temp3 = array();

        for ($i = 1; $i < $samples; $i++) {
            $options[] = "DEF:".$slavegroup->getId()."pin0p$i=$file:".$this->datasource."$i:AVERAGE";
            $options[] = "CDEF:".$slavegroup->getId()."p0p$i=".$slavegroup->getId()."pin0p$i,UN,0,".$slavegroup->getId()."pin0p$i,IF";
            if($i > 1) {
                $temp[] = $slavegroup->getId()."p0p$i,UN,+";
                $temp2[] = $slavegroup->getId()."p0p$i,+";
                $temp3[] = $slavegroup->getId()."p0p$i,".$slavegroup->getId()."m0,-,DUP,*,+";
            }
        }

        $options[] = "CDEF:".$slavegroup->getId()."samples=$samples,".$slavegroup->getId()."p0p1,UN,".implode(",", $temp).",-";
        $options[] = "CDEF:".$slavegroup->getId()."m0=".$slavegroup->getId()."p0p1,".implode(",", $temp2).",".$slavegroup->getId()."samples,/";
        $options[] = "CDEF:".$slavegroup->getId()."sdev0=".$slavegroup->getId()."p0p1,".$slavegroup->getId()."m0,-,DUP,*,".implode(",", $temp3).",".$slavegroup->getId()."samples,/,SQRT";
    }
}