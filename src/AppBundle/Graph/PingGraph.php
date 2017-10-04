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
use AppBundle\Entity\SlaveGroup;
use AppBundle\Exception\RrdException;

class PingGraph extends RrdGraph
{
    public function getSummaryGraph(Device $device, Probe $probe)
    {
        $colors = array(
            array(
                'main' => '#0000ff',
                'stddev' => '#0000ff44'
            ),
            array(
                'main' => '#00ff00',
                'stddev' => '#00ff0044'
            ),
            array(
                'main' => '#ff0000',
                'stddev' => '#ff000044'
            ),
        );

        $slavegroups = $device->getSlaveGroups()->toArray();
        $domain = $device->getDomain();
        do {
            $slavegroups = array_merge($slavegroups, $domain->getSlaveGroups()->toArray());
            $domain = $domain->getParent();
        } while ($domain != null);

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

        $counter = 0;
        foreach ($slavegroups as $slavegroup) {

            $file = $this->storage->getFilePath($device, $probe, $slavegroup);
            if (!file_exists($file)) {
                continue;
            }

            $options[] = sprintf("DEF:%s=%s:%s:%s", $slavegroup->getId() . '-median', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "AVERAGE");
            $options[] = sprintf("DEF:%s=%s:%s:%s", $slavegroup->getId() . '-loss', $this->storage->getFilePath($device, $probe, $slavegroup), 'loss', "AVERAGE");
            $options[] = "CDEF:" . $slavegroup->getId() . "-dm0=" . $slavegroup->getId()."-median,0,100000,LIMIT";
            $options[] = sprintf("CDEF:%s=%s,%s,%s", $slavegroup->getId() . '-loss_percent', $slavegroup->getId() . "-loss", "100", "*");
            $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe, $slavegroup), $probe->getSamples(), $slavegroup);

            $options[] = "CDEF:" . $slavegroup->getId() . "-dmlow0=" . $slavegroup->getId() . "-dm0," . $slavegroup->getId() . "-sdev0,2,/,-";
            $options[] = "CDEF:" . $slavegroup->getId() . "-s2d0=" . $slavegroup->getId() . "-sdev0";
            $options[] = sprintf("LINE:%s%s:%s", $slavegroup->getId()."-median", $colors[$counter % 3]['main'], sprintf("%-15s", $slavegroup->getName()));
            $options[] = sprintf("AREA:%s", $slavegroup->getId() . '-dmlow0');
            $options[] = "AREA:" . $slavegroup->getId() . "-s2d0".$colors[$counter % 3]['stddev']."::STACK";

            $options[] = "VDEF:" . $slavegroup->getId() . "-avsd0=" . $slavegroup->getId() . "-sdev0,AVERAGE";
            $options[] = sprintf("GPRINT:%s:%s:%s", $slavegroup->getId()."-median", 'AVERAGE', "%7.2lf ms av md");
            $options[] = sprintf("GPRINT:%s:%s:%s", $slavegroup->getId() .'-loss_percent', 'AVERAGE', "%7.2lf %% av ls");
            $options[] = sprintf("GPRINT:%s:%s", $slavegroup->getId() .'-avsd0', "%7.2lf ms av sd");
            $options[] = "COMMENT: \\n";

            $counter++;
        }

        if ($counter == 0) {
            return dirname(__FILE__)."/../../../web/notfound.png";
        }

        $options[] = "COMMENT:".date("D M j H\\\:i\\\:s Y")." \\r";

        $return = rrd_graph($imageFile, $options);
        $error = rrd_error();
        if (!$return || $error) {
            throw new RrdException($error);
        }

        return $imageFile;
    }

    public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = "now", $debug = false)
    {
        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        if (!file_exists($file)) {
            return dirname(__FILE__)."/../../../web/notfound.png";;
        }

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
            "--end", $end,
            "--title=$title",
            "--vertical-label=ms",
            "--lower-limit=0",
            "--upper-limit=".$this->getMedianMax($start, $this->storage->getFilePath($device, $probe, $slavegroup)),
            "--rigid",
            "--width=1000",
            "--height=200",
        );

        $options[] = sprintf("DEF:%s=%s:%s:%s",'median', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "AVERAGE");
        $options[] = sprintf("DEF:%s=%s:%s:%s",'loss', $this->storage->getFilePath($device, $probe, $slavegroup), 'loss', "AVERAGE");

        if ($debug) {
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'hwpredict', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "HWPREDICT");
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'devpredict', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "DEVPREDICT");
            $options[] = sprintf("DEF:%s=%s:%s:%s", 'failures', $this->storage->getFilePath($device, $probe, $slavegroup), 'median', "FAILURES");
        }

        $options[] = "CDEF:dm0=median,0,$max,LIMIT";
        $options[] = sprintf("CDEF:%s=%s,%s,%s",'loss_percent', "loss", "100", "*");
        $this->calculateStdDev($options, $this->storage->getFilePath($device, $probe, $slavegroup), $probe->getSamples(), $slavegroup);
        $options[] = "CDEF:s2d0=".$slavegroup->getId()."-sdev0";

        $options[] = "CDEF:lossred=loss,0.2,GT,median,UNKN,IF";
        $options[] = "CDEF:lossorange=loss,0.05,GE,median,UNKN,IF";
        $options[] = "CDEF:lossgreen=loss,0.05,LT,median,UNKN,IF";

        if ($debug) {
            $options[] = "CDEF:upper=hwpredict,devpredict,2,*,+";
            $options[] = "CDEF:lower=hwpredict,devpredict,2,*,-";
        }

        if ($debug) {
            $options[] = sprintf("TICK:%s%s:%s", 'failures', '#fdd017', '1.0');
        }

        $total = $probe->getSamples();
        $file = $this->storage->getFilePath($device, $probe, $slavegroup);
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $options[] = "DEF:ping$i=$file:ping$i:AVERAGE";
            $options[] = "CDEF:cp$i=ping$i,$max,LT,ping$i,INF,IF";
        }
        for ($i = 1; $i <= $probe->getSamples(); $i++) {
            $options[] = "CDEF:smoke$i=cp$i,UN,UNKN,cp$total,cp$i,-,IF";
            $options[] = "AREA:cp$i";
            $options[] = "STACK:smoke$i#33333322";
        }

        if ($debug) {
            $options[] = sprintf("LINE1:%s%s", 'upper', '#ff0000');
            $options[] = sprintf("LINE1:%s%s", 'lower', '#0000ff');
            $options[] = sprintf("LINE1:%s%s", 'hwpredict', '#ff00ff');
        }

        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossgreen', '#00ff00');
        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossorange', '#ff9900');
        $options[] = sprintf("%s:%s%s", 'LINE1', 'lossred', '#ff0000');

        $options[] = "GPRINT:median:AVERAGE:median rtt\: %7.2lf ms avg";
        $options[] = "GPRINT:median:MAX:%7.2lf ms max";
        $options[] = "GPRINT:median:MIN:%7.2lf ms min";
        $options[] = "GPRINT:median:LAST:%7.2lf ms now";
        $options[] = "GPRINT:s2d0:AVERAGE:%7.2lf ms sd";
        $options[] = "COMMENT: \\n";

        $options[] = "GPRINT:loss:AVERAGE:packet loss\: %7.2lf %% avg";
        $options[] = "GPRINT:loss:MAX:%8.2lf %% max";
        $options[] = "GPRINT:loss:MIN:%8.2lf %% min";
        $options[] = "GPRINT:loss:LAST:%8.2lf %% now";
        $options[] = "COMMENT: \\n";

        $options[] = "COMMENT:".$probe->getName()." (".$probe->getSamples()." probes of type ".$probe->getType()." in ".$probe->getStep()." seconds) from ".$slavegroup->getName();
        $options[] = "COMMENT:ending on ".date("D M j H\\\:i\\\:s Y", $end);

        $return = rrd_graph($imageFile, $options);
        $error = rrd_error();
        if (!$return || $error) {
            throw new RrdException($error);
        }

        //var_dump($options);

        return $imageFile;
    }

    private function calculateStdDev(&$options, $file, $pings, $slavegroup)
    {
        $temp = array();
        $temp2 = array();
        $temp3 = array();

        for ($i = 1; $i < $pings; $i++) {
            $options[] = "DEF:".$slavegroup->getId()."-pin0p$i=$file:ping$i:AVERAGE";
            $options[] = "CDEF:".$slavegroup->getId()."-p0p$i=".$slavegroup->getId()."-pin0p$i,UN,0,".$slavegroup->getId()."-pin0p$i,IF";
            if($i > 1) {
                $temp[] = $slavegroup->getId()."-p0p$i,UN,+";
                $temp2[] = $slavegroup->getId()."-p0p$i,+";
                $temp3[] = $slavegroup->getId()."-p0p$i,".$slavegroup->getId()."-m0,-,DUP,*,+";
            }
        }

        $options[] = "CDEF:".$slavegroup->getId()."-pings0=$pings,".$slavegroup->getId()."-p0p1,UN,".implode(",", $temp).",-";
        $options[] = "CDEF:".$slavegroup->getId()."-m0=".$slavegroup->getId()."-p0p1,".implode(",", $temp2).",".$slavegroup->getId()."-pings0,/";
        $options[] = "CDEF:".$slavegroup->getId()."-sdev0=".$slavegroup->getId()."-p0p1,".$slavegroup->getId()."-m0,-,DUP,*,".implode(",", $temp3).",".$slavegroup->getId()."-pings0,/,SQRT";
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