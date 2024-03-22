<?php

namespace App\Graph;

use App\Entity\Slave;
use App\Storage\SlaveStatsRrdStorage;

class SlaveGraph
{
    public function __construct(private readonly SlaveStatsRrdStorage $storage)
    {
    }

    public function getGraph(Slave $slave, $type, $start = -3600, $end = null, $debug = false)
    {
        if (!$end) {
            $end = date('U');
        }

        $file = $this->storage->getFilePath($slave, $type);
        if (!file_exists($file)) {
            return file_get_contents(__DIR__.'/../../public/notfound.png');
        }

        $max = 100000;

        if ($start < 0) {
            $start = date('U') + $start;
        }
        $title = $slave->getId()." - $type";

        $options = [
            '--slope-mode',
            '--border=0',
            '--start', $start,
            '--end', $end,
            "--title=$title",
            '--vertical-label='.$this->getAxisLabel($type),
            '--lower-limit=0',
            //"--upper-limit=".$this->getMax($slave, $start, $end, $this->storage->getFilePath($slave, $type)),
            '--rigid',
            '--width=1000',
            '--height=200',
            '--color=BACK'.$_ENV['RRD_BACKGROUND'],
        ];

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
            case 'load':
                $options = $this->createLoadGraph($slave, $file, $options);
                break;
            case 'memory':
                $options = $this->createMemoryGraph($slave, $file, $options);
                break;
        }

        $options[] = 'COMMENT: \\n';

        //$options[] = "COMMENT:".$probe->getName()." (".$probe->getSamples()." probes of type ".$probe->getType()." in ".$probe->getStep()." seconds) from ".$slavegroup->getName()."";
        $options[] = 'COMMENT:ending on '.date("D M j H\\\:i\\\:s Y", $end).'';

        return $this->storage->graph($options);
    }

    private function getAxisLabel($type) {
        return match ($type) {
            'queues' => "messages",
            'load' => "processes",
            'memory' => "bytes",
            default => $type,
        };
    }

    public function createPostGraph($slave, $options)
    {
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'failed', $this->storage->getFilePath($slave, 'posts'), 'failed', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'discarded', $this->storage->getFilePath($slave, 'posts'), 'discarded', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'successful', $this->storage->getFilePath($slave, 'posts'), 'successful', 'AVERAGE');

        $options[] = sprintf('LINE1:%s%s:%s', 'failed', '#ff0000', "failed\t");

        $options[] = "GPRINT:failed:AVERAGE:\: %6.1lf avg";
        $options[] = 'GPRINT:failed:MAX:%7.1lf max';
        $options[] = 'GPRINT:failed:MIN:%7.1lf min';
        $options[] = 'GPRINT:failed:LAST:%7.1lf now';
        $options[] = 'COMMENT: \\n';

        $options[] = sprintf('LINE1:%s%s:%s', 'discarded', '#0000ff', "discarded\t");

        $options[] = "GPRINT:discarded:AVERAGE:\: %6.1lf avg";
        $options[] = 'GPRINT:discarded:MAX:%7.1lf max';
        $options[] = 'GPRINT:discarded:MIN:%7.1lf min';
        $options[] = 'GPRINT:discarded:LAST:%7.1lf now';
        $options[] = 'COMMENT: \\n';

        $options[] = sprintf('LINE1:%s%s:%s', 'successful', '#00ff00', "successful\t");

        $options[] = "GPRINT:successful:AVERAGE:\: %6.1lf avg";
        $options[] = 'GPRINT:successful:MAX:%7.1lf max';
        $options[] = 'GPRINT:successful:MIN:%7.1lf min';
        $options[] = 'GPRINT:successful:LAST:%7.1lf now';
        $options[] = 'COMMENT: \\n';

        return $options;
    }

    public function createWorkersGraph($slave, $file, $options)
    {
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'total', $this->storage->getFilePath($slave, 'workers'), 'total', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'available', $this->storage->getFilePath($slave, 'workers'), 'available', 'AVERAGE');

        //$options[] = sprintf("AREA:%s%s", 'total', '#00ff00');

        $temp = $this->storage->getDataSources($file);
        $datasources = [];
        if (in_array('ping', $temp)) {
            $datasources[] = 'ping';
            unset($temp[array_search('ping', $temp)]);
        }
        if (in_array('traceroute', $temp)) {
            $datasources[] = 'traceroute';
            unset($temp[array_search('traceroute', $temp)]);
        }
        $datasources = array_merge($datasources, $temp);
        $index = 0;
        foreach ($datasources as $datasource) {
            if ('total' != $datasource && 'available' != $datasource) {
                $options[] = sprintf('DEF:%s=%s:%s:%s', $datasource, $this->storage->getFilePath($slave, 'workers'), $datasource, 'AVERAGE');
                if (0 == $index) {
                    $options[] = sprintf('AREA:%s%s:%s', $datasource, $this->getColor($index, count($this->storage->getDataSources($file))), sprintf('%-10s', $datasource));
                } else {
                    $options[] = sprintf('STACK:%s%s:%s', $datasource, $this->getColor($index, count($this->storage->getDataSources($file))), sprintf('%-10s', $datasource));
                }
                ++$index;

                $options[] = "GPRINT:$datasource:AVERAGE:\: %6.1lf avg";
                $options[] = "GPRINT:$datasource:MAX:%7.1lf max";
                $options[] = "GPRINT:$datasource:MIN:%7.1lf min";
                $options[] = 'COMMENT: \\n';
            }
        }
        $options[] = sprintf('STACK:%s%s:%s', 'available', '#eeeeee', sprintf('%-10s', 'available'));

        $options[] = "GPRINT:available:AVERAGE:\: %6.1lf avg";
        $options[] = 'GPRINT:available:MAX:%7.1lf max';
        $options[] = 'GPRINT:available:MIN:%7.1lf min';
        $options[] = 'COMMENT: \\n';

        return $options;
    }

    public function createQueuesGraph($slave, $file, $options)
    {
        $sources = $this->storage->getDataSources($file);

        $index = 0;
        foreach ($sources as $source) {
            $options[] = sprintf('DEF:%s=%s:%s:%s', $source, $this->storage->getFilePath($slave, 'queues'), $source, 'AVERAGE');
            $options[] = sprintf('LINE1:%s%s:%s', $source, $this->getColor($index, count($this->storage->getDataSources($file))), sprintf('%-7s', ucfirst((string) $source)));

            $options[] = "GPRINT:$source:AVERAGE:\: %7.1lf avg";
            $options[] = "GPRINT:$source:MAX:%7.1lf max";
            $options[] = "GPRINT:$source:MIN:%7.1lf min";
            $options[] = "GPRINT:$source:LAST:%7.1lf now";
            $options[] = 'COMMENT: \\n';

            ++$index;
        }

        return $options;
    }

    public function createLoadGraph($slave, $file, $options)
    {
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'load1', $this->storage->getFilePath($slave, 'load'), 'load1', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'load5', $this->storage->getFilePath($slave, 'load'), 'load5', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'load15', $this->storage->getFilePath($slave, 'load'), 'load15', 'AVERAGE');

        $options = $this->addLine($options, 1, 3, ' 1 minute load average', 'load1');
        $options = $this->addLine($options, 2, 3, ' 5 minute load average', 'load5');
        $options = $this->addLine($options, 3, 3, '15 minute load average', 'load15');

        return $options;
    }

    public function createMemoryGraph($slave, $file, $options)
    {
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'total_bytes', $this->storage->getFilePath($slave, 'memory'), 'total', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'used_bytes', $this->storage->getFilePath($slave, 'memory'), 'used', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'free_bytes', $this->storage->getFilePath($slave, 'memory'), 'free', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'shared_bytes', $this->storage->getFilePath($slave, 'memory'), 'shared', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'buffer_bytes', $this->storage->getFilePath($slave, 'memory'), 'buffer', 'AVERAGE');
        $options[] = sprintf('DEF:%s=%s:%s:%s', 'available_bytes', $this->storage->getFilePath($slave, 'memory'), 'available', 'AVERAGE');

        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'total', 'total_bytes', 1000, '*');
        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'used', 'used_bytes', 1000, '*');
        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'free', 'free_bytes', 1000, '*');
        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'shared', 'shared_bytes', 1000, '*');
        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'buffer', 'buffer_bytes', 1000, '*');
        $options[] = sprintf('CDEF:%s=%s,%s,%s', 'available', 'available_bytes', 1000, '*');

        $options = $this->addLine($options, 1, 6, 'total', 'total');
        $options = $this->addLine($options, 2, 6, 'used', 'used');
        $options = $this->addLine($options, 3, 6, 'free', 'free');
        $options = $this->addLine($options, 4, 6, 'shared', 'shared');
        $options = $this->addLine($options, 5, 6, 'buffer', 'buffer');
        $options = $this->addLine($options, 6, 6, 'available', 'available');

        return $options;
    }

    private function addLine($options, $index, $total, $label, $name)
    {
        $options[] = sprintf('LINE1:%s%s:%s', $name, $this->getColor($index, $total), sprintf('%-9s', $label));

        $options[] = "GPRINT:$name:AVERAGE:\: %7.1lf%s avg";
        $options[] = "GPRINT:$name:MAX:%7.1lf%s max";
        $options[] = "GPRINT:$name:MIN:%7.1lf%s min";
        $options[] = "GPRINT:$name:LAST:%7.1lf%s now";
        $options[] = 'COMMENT: \\n';

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

        return '#'.sprintf('%02x', $red).sprintf('%02x', $green).sprintf('%02x', $blue);
    }
}
