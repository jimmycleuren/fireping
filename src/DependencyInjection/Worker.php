<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 4/09/2018
 * Time: 11:18
 */

namespace App\DependencyInjection;

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

    public function __construct(WorkerManager $manager, KernelInterface $kernel, LoggerInterface $logger, int $timeout, int $idleTimeout)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $executable = $kernel->getRootDir() . '/../bin/console';
        $environment = $kernel->getEnvironment();
        $this->process = new Process("exec php $executable app:probe:worker --env=$environment");
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
                    $this->logger->info("$this received a valid json, calling callback");
                    ($this->callback)($type, $this->receiveBuffer);

                    $this->release();
                }
            }
        );
    }

    public function stop()
    {
        $this->process->stop(3, SIGINT);
        $this->receiveBuffer = null;
        $this->input = null;
        $this->startTime = null;
        $this->expectedRuntime = null;
    }

    public function release()
    {
        $this->receiveBuffer = '';
        $this->startTime = null;
        $this->expectedRuntime = null;
        $this->manager->release($this);
    }

    public function send($data, $expectedRuntime, \Closure $callback)
    {
        $this->startTime = microtime(true);
        $this->expectedRuntime = $expectedRuntime;
        $this->callback = $callback;

        $this->input->write($data);
    }

    public function loop()
    {
        if ($this->startTime != null && $this->expectedRuntime != null) {
            $actualRuntime = microtime(true) - $this->startTime;
            $expectedRuntime = $this->expectedRuntime * 1.25;
            if ($actualRuntime > $expectedRuntime) {
                $this->logger->info("Worker $this has exceeded the expected runtime, terminating.");
            }
        }
        $this->process->checkTimeout();
        $this->process->getIncrementalOutput();
    }

    public function __toString()
    {
        return "#".$this->process->getPid();
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