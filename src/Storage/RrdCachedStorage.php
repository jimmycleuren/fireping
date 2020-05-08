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

class RrdCachedStorage extends RrdStorage
{
    private $daemon = "unix:///var/run/rrdcached.sock";
    private $connections = [];

    public function __construct($path, LoggerInterface $logger)
    {
        parent::__construct($path, $logger);

        $finder = new ExecutableFinder();
        if (!$rrdtool = $finder->find("rrdtool", null, ['/usr/bin'])) {
            throw new \Exception("rrdtool is not installed on this system.");
        }
    }

    private function connect($daemon)
    {
        $socket = stristr($daemon, "unix://") ? $daemon : "tcp://$daemon";
        if(!isset($this->connections[$daemon]) || !$this->connections[$daemon]) {
            $this->connections[$daemon] = stream_socket_client($socket, $errno, $errstr, 5);
            stream_set_timeout($this->connections[$daemon], 5);
        }
    }

    private function send($command, $daemon)
    {
        if(!fwrite($this->connections[$daemon], $command.PHP_EOL)) {
            throw new RrdException("Could not write to rrdcached");
        }
    }
    private function read($daemon)
    {
        $line = fgets($this->connections[$daemon], 8192);
        $result = $line;
        $code = explode(" ", $line);
        $code = $code[0];
        for($i = 0; $i < $code; $i++) {
            $result .= "\n".fgets($this->connections[$daemon], 8192);
        }

        return $result;
    }

    public function getFilePath(Device $device, Probe $probe, SlaveGroup $group)
    {
        return $device->getId()."/".$probe->getId()."/".$group->getId().'.rrd';
    }

    /**
     * @param Device $device
     * @param Probe $probe
     * @param SlaveGroup $group
     * @param int $timestamp
     * @param array $data
     * @param bool $addNewSources
     * @param string $daemon
     * @throws RrdException
     * @throws WrongTimestampRrdException
     */
    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources = false, $daemon = null)
    {
        $path = $this->getFilePath($device, $probe, $group);

        if (!$this->fileExists($device, $path, $daemon)) {
            $this->create($device, $probe, $group, $timestamp, $data, $daemon);
        }
        $this->update($device, $probe, $group, $timestamp, $data, $addNewSources, $daemon);
    }

    public function fileExists(Device $device, $path, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $this->connect($daemon);

        $this->send("INFO $path", $daemon);
        $message = $this->read($daemon);
        if (stristr($message, "rrd_version")) {
            return true;
        }
        return false;
    }

    protected function create(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, $daemon = null)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $start = $timestamp - 1;

        $options = array(
            "-b", $start,
            "-s", $probe->getStep()
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

        $this->send("CREATE $filename ".implode(" ", $options), $daemon);
        $message = $this->read($daemon);
        if (!stristr($message, "0 RRD created OK")) {
            throw new RrdException(trim($message));
        }
    }

    private function getLastUpdate($filename, $daemon)
    {
        $this->connect($daemon);
        $this->send('LAST '.$filename, $daemon);
        $message = $this->read($daemon);
        if (!trim($message)) {
            throw new RrdException(trim($message));
        }
        $timestamp = explode(" ", $message)[1];
        return $timestamp;
    }

    /**
     * @param Device $device
     * @param Probe $probe
     * @param SlaveGroup $group
     * @param int $timestamp
     * @param array $data
     * @param bool $addNewSources
     * @param string $daemon
     * @throws RrdException
     * @throws WrongTimestampRrdException
     */
    protected function update(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources, $daemon = null)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $last = $this->getLastUpdate($filename, $daemon);
        if ($last >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$last.", cannot update at ".$timestamp);
        }

        $originalData = $data;

        $sources = $this->getDatasources($device, $probe, $group, $daemon);

        $values = array($timestamp);
        foreach($sources as $source) {
            if (isset($data[$source])) {
                $values[] = $data[$source];
                unset($data[$source]);
            } else {
                $values[] = "U";
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

                    $sources = $this->getDatasources($device, $probe, $group, $daemon);

                    $data = $originalData;
                    $values = array($timestamp);
                    foreach ($sources as $source) {
                        if (isset($data[$source])) {
                            $values[] = $data[$source];
                        } else {
                            $values[] = "U";
                        }
                    }
                } catch(\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
                $lock->release();
            }
        }

        $this->send("UPDATE $filename ".implode(":", $values), $daemon);
        $message = $this->read($daemon);
        if (!stristr($message, "0 errors")) {
            $this->logger->warning($message);
        }
    }

    public function getDatasources(Device $device, Probe $probe, SlaveGroup $group, $daemon = null)
    {
        $filename = $this->getFilePath($device, $probe, $group);

        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $this->flush($filename, $daemon);

        $this->connect($daemon);

        $sources = array();
        $this->send("INFO $filename", $daemon);
        $message = $this->read($daemon);
        $message = explode("\n", $message);
        foreach($message as $line) {
            if(preg_match("/ds\[([\w]+)\]/", $line, $match)) {
                if (!in_array($match[1], $sources)) {
                    $sources[] = $match[1];
                }
            }
        }

        return $sources;
    }

    public function graph(Device $device, $options, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $imageFile = tempnam("/tmp", 'image');

        foreach($options as $key => $option) {
            $options[$key] = '"'.$option.'"';
        }

        $process = Process::fromShellCommandline("rrdtool graph $imageFile -d $daemon ".implode(" ", $options));
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
     * @param int $timestamp
     * @param string $key
     * @param string $function
     * @param string $daemon
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

    public function getGraphValue(Device $device, $options, $daemon = null)
    {
        if (!$daemon) {
            $daemon = $this->daemon;
        }

        $tempFile = tempnam("/tmp", 'temp');

        $process = new Process(array_merge(["rrdtool", "graph", $tempFile, "-d", $daemon], $options));
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

    protected function addDataSource(Device $device, $filename, $name, Probe $probe)
    {
        $ds = sprintf(
            "DS:%s:%s:%s:%s:%s",
            $name,
            'GAUGE',
            $probe->getStep() * 2,
            0,
            "U"
        );

        $process = new Process(["rrdtool", "tune", $this->path.$filename, $ds]);
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }
    }

    private function flush($filename, $daemon)
    {
        $this->connect($daemon);
        $this->send('FLUSH '.$filename, $daemon);
        $message = $this->read($daemon);

        if (!stristr($message, "0 errors")) {
            $this->logger->warning($message);
        }
    }
}