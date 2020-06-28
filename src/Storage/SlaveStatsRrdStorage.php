<?php

namespace App\Storage;

use App\Entity\Slave;
use App\Exception\RrdException;
use App\Exception\WrongTimestampRrdException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class SlaveStatsRrdStorage
{
    protected $logger;
    protected $path;

    protected $archives = [
        ['function' => 'AVERAGE', 'steps' => 1, 'rows' => 3600],
        ['function' => 'AVERAGE', 'steps' => 10, 'rows' => 8640],
        ['function' => 'MIN', 'steps' => 10, 'rows' => 8640],
        ['function' => 'MAX', 'steps' => 10, 'rows' => 8640],
        ['function' => 'AVERAGE', 'steps' => 60, 'rows' => 43200],
        ['function' => 'MIN', 'steps' => 60, 'rows' => 43200],
        ['function' => 'MAX', 'steps' => 60, 'rows' => 43200],
    ];

    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->path = $kernel->getProjectDir().'/var/rrd/slaves/';
    }

    public function getFilePath(Slave $slave, $type)
    {
        if (!file_exists($concurrentDirectory = $this->path) && !mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        $path = $this->path.$slave->getId();

        if (!file_exists($path) && !mkdir($path) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        return $this->path.$slave->getId().'/'.$type.'.rrd';
    }

    public function store(Slave $slave, string $type, $timestamp, $data)
    {
        $path = $this->getFilePath($slave, $type);

        if (!file_exists($path)) {
            $this->create($slave, $type, $timestamp, $data);
        }
        $this->update($slave, $type, $timestamp, $data);
    }

    protected function create(Slave $slave, string $type, $timestamp, $data)
    {
        $filename = $this->getFilePath($slave, $type);

        $start = $timestamp - 1;
        $step = in_array($type, ['posts', 'load', 'memory']) ? 60 : 1;

        $options = [
            '--start', $start,
            '--step', $step,
        ];
        foreach ($data as $key => $value) {
            $options[] = sprintf(
                'DS:%s:%s:%s:%s:%s',
                $key,
                'GAUGE',
                $step * 2,
                0,
                'U'
            );
        }

        foreach ($this->archives as $archive) {
            $options[] = sprintf(
                'RRA:%s:0.5:%s:%s',
                strtoupper($archive['function']),
                $archive['steps'],
                $archive['rows']
            );
        }

        $return = rrd_create($filename, $options);
        if (!$return) {
            $this->logger->error(print_r($options, true));
            throw new RrdException(rrd_error());
        }
    }

    protected function update(Slave $slave, string $type, $timestamp, $data)
    {
        $filename = $this->getFilePath($slave, $type);

        $info = rrd_info($filename);
        $update = rrd_lastupdate($filename);

        $step = in_array($type, ['posts', 'load', 'memory']) ? 60 : 1;

        if ($info['step'] != $step) {
            throw new RrdException('Steps are not equal, '.$step.' is configured, RRD file is using '.$info['step']);
        }

        if ($update['last_update'] >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$update['last_update'].', cannot update at '.$timestamp);
        }

        $dataSources = $this->getDataSources($filename);
        foreach ($data as $key => $value) {
            if (!in_array($key, $dataSources)) {
                $this->addDataSource($filename, $key);
            }
        }

        $template = [];
        $values = [$timestamp];

        foreach ($data as $key => $value) {
            $template[] = $key;
            $values[] = $value;
        }
        $options = ['-t', implode(':', $template), implode(':', $values)];

        $return = rrd_update($filename, $options);

        if (!$return) {
            throw new RrdException(rrd_error());
        }
    }

    /**
     * @return array
     *
     * @throws RrdException
     */
    public function getDataSources(string $filename)
    {
        $sources = [];
        $info = rrd_info($filename);

        if (!is_array($info)) {
            throw new RrdException("Could not read rrd info from $filename");
        }

        foreach ($info as $key => $value) {
            if (preg_match("/ds\[([\w]+)\]/", $key, $match)) {
                if (!in_array($match[1], $sources)) {
                    $sources[] = $match[1];
                }
            }
        }

        return $sources;
    }

    protected function addDataSource($filename, $name)
    {
        $step = 1;

        $ds = sprintf(
            'DS:%s:%s:%s:%s:%s',
            $name,
            'GAUGE',
            $step * 2,
            0,
            'U'
        );

        $process = new Process(['rrdtool', 'tune', $filename, $ds]);
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }
    }

    public function graph($options)
    {
        $imageFile = tempnam('/tmp', 'image');

        $ret = rrd_graph($imageFile, $options);
        if (!$ret) {
            throw new RrdException(rrd_error());
        }

        $return = file_get_contents($imageFile);
        unlink($imageFile);

        return $return;
    }
}
