<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Exception\WrongTimestampRrdException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class RrdCachedStorage extends RrdStorage
{
    private $daemon = "unix:///var/run/rrdcached.sock";

    public function __construct($path, LoggerInterface $logger)
    {
        parent::__construct($path, $logger);

        $finder = new ExecutableFinder();
        if (!$rrdtool = $finder->find("rrdtool", null, ['/usr/bin'])) {
            throw new \Exception("rrdtool is not installed on this system.");
        }
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, $daemon = null)
    {
        $path = $this->getFilePath($device, $probe, $group);

        if (!$this->fileExists($path, $daemon)) {
            $this->create($path, $probe, $timestamp, $data, $daemon);
        }
        $this->update($path, $probe, $timestamp, $data, $daemon);
    }

    public function fileExists($path, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $process = new Process("rrdtool info $path -d ".$daemon);
        $process->run();
        $output = $process->getOutput();
        $error = $process->getErrorOutput();

        if (trim($error) != "") {
            return false;
        }

        return true;
    }

    protected function create($filename, Probe $probe, $timestamp, $data, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $start = $timestamp - 1;

        $options = array(
            "--start", $start,
            "--step", $probe->getStep()
        );
        foreach ($data as $key => $value) {
            $options[] = sprintf(
                "DS:%s:%s:%s:%s:%s",
                $key,
                'GAUGE',
                $probe->getStep() * 2,
                0,
                "U"
            );
        }

        foreach ($probe->getArchives() as $archive) {
            $options[] = sprintf(
                "RRA:%s:0.5:%s:%s",
                strtoupper($archive->getFunction()),
                $archive->getSteps(),
                $archive->getRows()
            );
        }

        foreach ($this->predictions as $value) {
            $options[] = sprintf(
                "RRA:%s:%s:%s:%s:%s",
                strtoupper($value['function']),
                $value['rows'],
                $value['alpha'],
                $value['beta'],
                $value['period']
            );
        }

        $process = new Process("rrdtool create $filename -d ".$daemon . " ".implode(" ", $options));
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }
    }

    protected function update($filename, $probe, $timestamp, $data, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $last = $this->getLastUpdate($filename, $daemon);
        if ($last >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$last.", cannot update at ".$timestamp);
        }

        $sources = $this->getDatasources($filename, $daemon);

        $values = array($timestamp);
        foreach($sources as $source) {
            $values[] = $data[$source];
        }

        $process = new Process("rrdtool update $filename -d ".$daemon . " ".implode(":", $values));
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }
    }

    private function getLastUpdate($filename, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $process = new Process("rrdtool last $filename -d ".$daemon);
        $process->run();
        $output = $process->getOutput();

        return trim($output);
    }

    private function getDatasources($filename, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $sources = array();

        $process = new Process("rrdtool info $filename -d ".$daemon);
        $process->run();
        $output = $process->getOutput();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }

        $output = explode("\n", $output);
        foreach($output as $line) {
            if(preg_match("/ds\[([\w]+)\]/", $line, $match)) {
                if (!in_array($match[1], $sources)) {
                    $sources[] = $match[1];
                }
            }
        }

        return $sources;
    }

    public function graph($options, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $imageFile = tempnam("/tmp", 'image');

        foreach($options as $key => $option) {
            $options[$key] = '"'.$option.'"';
        }

        $process = new Process("rrdtool graph $imageFile -d ".$daemon." ".implode(" ", $options));
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }

        $return = file_get_contents($imageFile);
        unlink($imageFile);

        return $return;
    }

    /**
     * TODO: implement further
     * @param Device $device
     * @param Probe $probe
     * @param SlaveGroup $group
     * @param $timestamp
     * @param $key
     * @param $function
     * @param null $daemon
     * @return mixed|null|string|void
     */
    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        /*
        $path = $this->getFilePath($device, $probe, $group);

        $result = rrd_fetch($path, array($function, "--start", $timestamp - $probe->getStep()));

        if (!$result) {
            return null;
        }

        $value = reset($result['data'][$key]);

        if (is_nan($value)) {
            return "U";
        }

        return $value;
        */
    }

    public function getGraphValue($options, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $tempFile = tempnam("/tmp", 'temp');

        $process = new Process("rrdtool graph $tempFile -d ".$daemon." ".implode(" ", $options));
        $process->run();
        $data = $process->getOutput();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }

        unlink($tempFile);

        $data = explode("\n", $data);

        return (float)$data[1];
    }
}