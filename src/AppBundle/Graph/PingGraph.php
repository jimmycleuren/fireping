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

class PingGraph extends RrdGraph
{
    public function getSummaryGraph(Device $device, Probe $probe)
    {
        $file = $this->storage->getFilePath($device, $probe);
        if (!file_exists($file)) {
           return dirname(__FILE__)."/../../../web/notfound.png";
        }

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
        $options[] = "CDEF:dm0=median,0,100000,LIMIT";
        $options[] = sprintf("CDEF:%s=%s,%s,%s",'loss_percent', "loss", "100", "*");
        $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe), $probe->getSamples());

        $options[] = "CDEF:dmlow0=dm0,sdev0,2,/,-";
        $options[] = "CDEF:s2d0=sdev0";
        $options[] = sprintf("LINE:%s%s:%s", 'median', '#0000ff', 'median');
        $options[] = sprintf("AREA:%s", 'dmlow0');
        $options[] = "AREA:s2d0#0000FF44::STACK";

        $options[] = "VDEF:avsd0=sdev0,AVERAGE";
        $options[] = sprintf("GPRINT:%s:%s:%s", 'median', 'AVERAGE', "%7.2lf ms av md");
        $options[] = sprintf("GPRINT:%s:%s:%s", 'loss_percent', 'AVERAGE', "%7.2lf %% av ls");
        $options[] = sprintf("GPRINT:%s:%s", 'avsd0', "%7.2lf ms av sd");
        $options[] = "COMMENT: \\n";
        $options[] = "COMMENT:".date("D M j H\\\:i\\\:s Y")." \\r";

        $return = rrd_graph($imageFile, $options);
        $error = rrd_error();
        if (!$return || $error) {
            throw new RrdException($error);
        }

        return $imageFile;
    }

    public function getDetailGraph(Device $device, Probe $probe, $start = -3600)
    {
        $max = 100000;

        if ($start < 0) {
            $start = date("U") + $start;
        }
        $title = $device->getName();

        $imageFile = tempnam("/tmp", 'image');
        $options = array(
            "--slope-mode",
            "--border=0",
            "--start", $start,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            "--upper-limit=".$this->getMedianMax($start, $this->storage->getFilePath($device, $probe)),
            "--rigid",
            "--width=1000",
            "--height=200",
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe), 'loss', "AVERAGE");
        $options[] = "CDEF:dm0=median,0,$max,LIMIT";
        $options[] = sprintf("CDEF:%s=%s,%s,%s",'loss_percent', "loss", "100", "*");
        $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe), $probe->getSamples());
        $options[] = "CDEF:s2d0=sdev0";

        $options[] = "CDEF:lossred=loss,0.8,GT,median,UNKN,IF";
        $options[] = "CDEF:lossorange=loss,0.4,GE,median,UNKN,IF";
        $options[] = "CDEF:lossgreen=loss,0.4,LT,median,UNKN,IF";

        $total = $probe->getSamples();
        $file = $this->storage->getFilePath($device, $probe);
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $options[] = "DEF:ping$i=$file:ping$i:AVERAGE";
            $options[] = "CDEF:cp$i=ping$i,$max,LT,ping$i,INF,IF";
        }
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $options[] = "CDEF:smoke$i=cp$i,UN,UNKN,cp$total,cp$i,-,IF";
            $options[] = "AREA:cp$i";
            $options[] = "STACK:smoke$i#33333322";
        }

        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossgreen', '#00ff00');
        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossorange', '#ff9900');
        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossred', '#ff0000');

        $options[] = "GPRINT:median:AVERAGE:median rtt\: %7.2lf ms avg";
        $options[] = "GPRINT:median:MAX:%7.2lf ms max";
        $options[] = "GPRINT:median:MIN:%7.2lf ms min";
        $options[] = "GPRINT:median:LAST:%7.2lf ms now";
        $options[] = "GPRINT:s2d0:AVERAGE:%7.2lf ms sd";

        $options[] = "GPRINT:loss:AVERAGE:packet loss\: %7.2lf %% avg";
        $options[] = "GPRINT:loss:MAX:%7.2lf %% max";
        $options[] = "GPRINT:loss:MIN:%7.2lf %% min";
        $options[] = "GPRINT:loss:LAST:%7.2lf %% now";

        //$options[] = sprintf("GPRINT:%s:%s:%s", 'loss_percent', 'AVERAGE', "%7.2lf %% av ls");
        $options[] = "COMMENT: \\n";
        $options[] = "COMMENT:".date("D M j H\\\:i\\\:s Y")." \\r";

        $return = rrd_graph($imageFile, $options);
        $error = rrd_error();
        if (!$return || $error) {
            throw new RrdException($error);
        }

        //var_dump($options);

        return $imageFile;
    }

    private function calculateStdDev(&$options, $file, $pings)
    {
        $temp = array();
        $temp2 = array();
        $temp3 = array();

        for ($i = 1; $i < $pings; $i++) {
            $options[] = "DEF:pin0p$i=$file:ping$i:AVERAGE";
            $options[] = "CDEF:p0p$i=pin0p$i,UN,0,pin0p$i,IF";
            if($i > 1) {
                $temp[] = "p0p$i,UN,+";
                $temp2[] = "p0p$i,+";
                $temp3[] = "p0p$i,m0,-,DUP,*,+";
            }
        }

        $options[] = "CDEF:pings0=$pings,p0p1,UN,".implode(",", $temp).",-";
        $options[] = "CDEF:m0=p0p1,".implode(",", $temp2).",pings0,/";
        $options[] = "CDEF:sdev0=p0p1,m0,-,DUP,*,".implode(",", $temp3).",pings0,/,SQRT";
    }

    private function getMedianMax($start, $file)
    {
        $options = array(
            "--start", $start,
            "--width=600",
            "DEF:maxping=$file:median:AVERAGE",
            "PRINT:maxping:MAX:%le"
        );

        $tempFile = tempnam("/tmp", 'temp');
        $data = rrd_graph($tempFile, $options);
        $maxMedian = (float)$data['calcpr'][0];

        return $maxMedian * 1.2;
    }
}