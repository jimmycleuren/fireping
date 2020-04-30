<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 4/09/2018
 * Time: 11:18
 */

namespace App\DependencyInjection;

use App\Exception\WorkerTimedOutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class Worker
{
    private $manager;

    private $input;

    private $process;

    private $receiveBuffer = '';

    private $startTime;

    private $expectedRuntime;

    private $callback;

    private $logger;

    private $type;

    private $name;

    private $executing = false;

    private $lastCommand = null;

    public function __construct(WorkerManager $manager, KernelInterface $kernel, LoggerInterface $logger, int $timeout, int $idleTimeout)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $executable = $kernel->getProjectDir() . '/bin/console';
        $environment = $kernel->getEnvironment();
        $this->process = Process::fromShellCommandline("exec php $executable app:probe:worker --env=$environment");
        $this->input = new InputStream();

        $this->process->setInput($this->input);
        $this->process->setTimeout($timeout);
        $this->process->setIdleTimeout($idleTimeout);
    }

    public function start()
    {
        $this->logger->info('Starting new worker.');

        $this->process->start(
            function ($type, $data) {
                $this->receiveBuffer .= $data;

                if (json_decode($this->receiveBuffer, true)) {
                    ($this->callback)($type, $this->receiveBuffer);

                    $this->release();
                }
            }
        );

        $this->name = "#".$this->process->getPid();
    }

    public function stop()
    {
        $this->process->stop(3, SIGINT);
        $this->receiveBuffer = null;
        $this->input = null;
        $this->startTime = null;
        $this->expectedRuntime = null;
        $this->executing = false;
    }

    public function release()
    {
        $this->receiveBuffer = '';
        $this->startTime = null;
        $this->expectedRuntime = null;
        $this->executing = false;
        $this->manager->release($this);
    }

    public function send($data, $expectedRuntime, \Closure $callback)
    {
        $this->executing = true;
        $this->startTime = microtime(true);
        $this->expectedRuntime = $expectedRuntime;
        $this->callback = $callback;

        $this->lastCommand = $data;
        $this->input->write($data);
    }

    public function loop()
    {
        if ($this->startTime != null && $this->expectedRuntime != null) {
            $actualRuntime = microtime(true) - $this->startTime;
            $expectedRuntime = $this->expectedRuntime * 1.25;
            if ($actualRuntime > $expectedRuntime) {
                throw new WorkerTimedOutException($expectedRuntime, $this->lastCommand);
            }
        }
        if (!$this->executing) {
            $this->process->checkTimeout();
        }
        $this->process->getIncrementalOutput();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}