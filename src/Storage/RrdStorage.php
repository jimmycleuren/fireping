<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Exception\WrongTimestampRrdException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class RrdStorage extends Storage
{
    protected $logger = null;
    protected $path = null;

    protected $predictions = [
        [
            'function' => 'HWPREDICT',
            'rows' => 51840,
            'alpha' => 0.1,
            'beta' => 0.0035,
            'period' => 1440,
        ],
    ];

    public function __construct($path, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->path = $path;

        if ($path && !file_exists($concurrentDirectory = $this->path) && !mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    public function getFilePath(Device $device, Probe $probe, SlaveGroup $group)
    {
        $path = $this->path.$device->getId();

        if (!file_exists($path) && !mkdir($path) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        $path = $this->path.$device->getId().'/'.$probe->getId();

        if (!file_exists($path) && !mkdir($path) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        return $this->path.$device->getId().'/'.$probe->getId().'/'.$group->getId().'.rrd';
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources = false)
    {
        $path = $this->getFilePath($device, $probe, $group);

        if (!$this->fileExists($device, $path)) {
            $this->create($device, $probe, $group, $timestamp, $data);
        }
        $this->update($device, $probe, $group, $timestamp, $data, $addNewSources);
    }

    public function fileExists(Device $device, $path)
    {
        return file_exists($path);
    }

    protected function create(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data)
    {
        if (count($probe->getArchives()) == 0) {
            $this->logger->error("No archives specified for probe ".$probe->getName());
            return;
        }

        if (count($data) == 0) {
            $this->logger->error("No data specified for probe ".$probe->getName());
            return;
        }

        $filename = $this->getFilePath($device, $probe, $group);

        $start = $timestamp - 1;

        $options = [
            '--start', $start,
            '--step', $probe->getStep(),
        ];
        foreach ($data as $key => $value) {
            $options[] = sprintf(
                'DS:%s:%s:%s:%s:%s',
                $key,
                'GAUGE',
                $probe->getStep() * 2,
                0,
                'U'
            );
        }

        foreach ($probe->getArchives() as $archive) {
            $options[] = sprintf(
                'RRA:%s:0.5:%s:%s',
                strtoupper($archive->getFunction()),
                $archive->getSteps(),
                $archive->getRows()
            );
        }

        foreach ($this->predictions as $value) {
            $options[] = sprintf(
                'RRA:%s:%s:%s:%s:%s',
                strtoupper($value['function']),
                $value['rows'],
                $value['alpha'],
                $value['beta'],
                $value['period']
            );
        }

        $return = rrd_create($filename, $options);
        if (!$return) {
            throw new RrdException(rrd_error());
        }
    }

    protected function update(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        $info = rrd_info($filename);
        $update = rrd_lastupdate($filename);

        if ($info['step'] != $probe->getStep()) {
            throw new RrdException('Steps are not equal, '.$probe->getStep().' is configured, RRD file is using '.$info['step']);
        }

        if ($update['last_update'] >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$update['last_update'].', cannot update at '.$timestamp);
        }

        $originalData = $data;

        $sources = $this->getDatasources($device, $probe, $group);

        $template = [];
        $values = [$timestamp];

        foreach ($sources as $source) {
            $template[] = $source;
            if (isset($data[$source])) {
                $values[] = $data[$source];
                unset($data[$source]);
            } else {
                $values[] = 'U';
            }
        }

        if ($addNewSources) {
            $store = new FlockStore(sys_get_temp_dir());
            $factory = new LockFactory($store);
            $lock = $factory->createLock('update-'.$filename);

            if ($lock->acquire(true)) {
                try {
                    foreach ($data as $name => $value) {
                        $this->logger->info("Adding new datasource $name to $filename");
                        $this->addDataSource($device, $filename, $name, $probe);
                    }

                    $sources = $this->getDatasources($device, $probe, $group);

                    $data = $originalData;

                    $template = [];
                    $values = [$timestamp];
                    foreach ($sources as $source) {
                        $template[] = $source;
                        if (isset($data[$source])) {
                            $values[] = $data[$source];
                        } else {
                            $values[] = 'U';
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
                $lock->release();
            }
        }

        $options = ['-t', implode(':', $template), implode(':', $values)];

        $return = rrd_update($filename, $options);

        $this->logger->debug("Updating $filename with ".print_r($options, true));

        if (!$return) {
            throw new RrdException(rrd_error());
        }
    }

    public function getDatasources(Device $device, Probe $probe, SlaveGroup $group, $daemon = null)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        $sources = [];
        $info = rrd_info($filename);

        foreach ($info as $key => $value) {
            if (preg_match("/ds\[([\w]+)\]/", $key, $match)) {
                if (!in_array($match[1], $sources)) {
                    $sources[] = $match[1];
                }
            }
        }

        return $sources;
    }

    protected function addDataSource(Device $device, $filename, $name, Probe $probe)
    {
        $ds = sprintf(
            'DS:%s:%s:%s:%s:%s',
            $name,
            'GAUGE',
            $probe->getStep() * 2,
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

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function)
    {
        $path = $this->getFilePath($device, $probe, $group);

        $result = rrd_fetch($path, [$function, '--start', $timestamp - $probe->getStep()]);

        if (!$result || !$result['data'] || !isset($result['data'][$key]) || !$result['data'][$key]) {
            return null;
        }

        $value = reset($result['data'][$key]);

        if (is_nan($value)) {
            return 'U';
        }

        return $value;
    }

    public function fetchAll(Device $device, Probe $probe, SlaveGroup $group, $start, $end, $datasource, $function)
    {
        $path = $this->getFilePath($device, $probe, $group);

        $result = rrd_fetch($path, array($function, "--start", $start, "--end", $end));

        if (!$result || !$result['data'] || !isset($result['data'][$datasource]) || !$result['data'][$datasource]) {
            return null;
        }

        foreach($result['data'][$datasource] as $key => $value) {
            if (is_nan($value)) {
                $result['data'][$datasource][$key] = "U";
            }
        }

        return $result['data'][$datasource];
    }

    public function validate(Device $device, Probe $probe, SlaveGroup $group)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        $finder = new ExecutableFinder();
        if (!$rrdtool = $finder->find('rrdtool')) {
            throw new \Exception('rrdtool is not installed on this system.');
        }

        $info = rrd_info($filename);

        if (!$info || !$info['step']) {
            $this->logger->warning("Could not read info from $filename");

            return;
        }

        if ($info['step'] != $probe->getStep()) {
            $this->logger->info('Running rrdtune to change step from '.$info['step'].' to '.$probe->getStep());
        }

        $rra = $this->readArchives($filename);

        //add new rra's
        foreach ($probe->getArchives() as $archive) {
            $found = false;
            foreach ($rra as $key => $item) {
                if ($item['cf'] == $archive->getFunction() && $item['rows'] == $archive->getRows() && $item['pdp_per_row'] == $archive->getSteps()) {
                    $found = true;
                    unset($rra[$key]);
                }
            }
            if (!$found) {
                $this->logger->info("Adding $archive");
                $rradef = sprintf(
                    'RRA:%s:0.5:%s:%s',
                    strtoupper($archive->getFunction()),
                    $archive->getSteps(),
                    $archive->getRows()
                );
                $process = new Process(['rrdtool', 'tune', $filename, $rradef]);
                $process->run();
            }
        }

        $rra = $this->readArchives($filename);

        //delete obsolete rra's
        for ($i = count($rra) - 1; $i >= 0; --$i) {
            $item = $rra[$i];
            $found = false;
            foreach ($probe->getArchives() as $archive) {
                if ($item['cf'] == $archive->getFunction() && $item['rows'] == $archive->getRows() && $item['pdp_per_row'] == $archive->getSteps()) {
                    $found = true;
                }
            }
            if (!$found && in_array($item['cf'], ['AVERAGE', 'MIN', 'MAX'])) {
                $this->logger->info("Removing #$i ".$item['cf'].'-'.$item['pdp_per_row'].'-'.$item['rows']);
                $process = new Process(['rrdtool', 'tune', $filename, "DELRRA:$i"]);
                $process->run();
            }
        }
    }

    protected function readArchives($filename)
    {
        $info = rrd_info($filename);

        if (!$info || !$info['step']) {
            $this->logger->warning("Could not read info from $filename");

            return;
        }

        $rra = [];
        foreach ($info as $key => $item) {
            if ('rra' == substr($key, 0, 3)) {
                preg_match("/rra\[([\d]+)\]\.([\w\_]+)/", $key, $matches);
                $rra[$matches[1]][$matches[2]] = $item;
            }
        }

        return $rra;
    }

    public function graph(Device $device, $options)
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

    public function getGraphValue(Device $device, $options)
    {
        $tempFile = tempnam('/tmp', 'temp');

        $data = rrd_graph($tempFile, $options);

        if (!$data) {
            throw new RrdException(rrd_error());
        }

        unlink($tempFile);

        return (float) $data['calcpr'][0];
    }

    /**
     * @param string $path
     *
     * @return array|string|null
     */
    public function listItems($path)
    {
        $process = new Process(['ls', $path]);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->logger->info($buffer);
            }
        });

        if (empty($process->getOutput())) {
            return null;
        }

        $contentArray = explode("\n", $process->getOutput());
        $contentArray = array_filter(array_unique($contentArray));

        return $contentArray;
    }

    /**
     * @param array  $items
     * @param string $path
     */
    private function concatCollection($items, $path): array
    {
        return array_map(function ($item) use ($path) {
            return $this->concatPath($item, $path);
        }, $items);
    }

    /**
     * @param string $item
     * @param string $path
     */
    private function concatPath($item, $path): string
    {
        return $path.$item;
    }

    public function remove(array $items, string $path)
    {
        $path = rtrim($path, '/').'/';
        $items = $this->concatCollection($items, $path);

        $process = new Process(array_merge(['rm', '-rf'], $items));
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->logger->info($buffer);
            }
        });
    }
}
