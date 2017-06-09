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
            //"--slope-mode",
            "--start", $start,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            "--width=600",
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe), 'loss', "AVERAGE");
        $options[] = sprintf("VDEF:%s=%s,%s",'stdev', "median", "STDEV");
        $options[] = sprintf("CDEF:%s=%s,%s,%s",'loss_percent', "loss", "100", "*");
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

        $options[] = sprintf("%s:%s%s:%s", 'LINE2', 'lossgreen', '#00ff00', '0');
        $options[] = sprintf("%s:%s%s:%s", 'LINE2', 'lossorange', '#ff9900', '40%');
        $options[] = sprintf("%s:%s%s:%s", 'LINE2', 'lossred', '#ff0000', '80%');

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
}