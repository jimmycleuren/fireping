<?php

namespace App\Slave\Worker;

use App\Slave\Exception\WorkerTimedOutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class Worker implements \Stringable
{
    private $input;

    private $process;

    private $receiveBuffer = '';

    private $startTime;

    private $expectedRuntime;

    private $callback;

    private $type;

    private $name = 'unknown';

    private $executing = false;

    private $lastTask = null;

    public function __construct(private readonly WorkerManager $manager, KernelInterface $kernel, private readonly LoggerInterface $logger, int $timeout, int $idleTimeout)
    {
        $executable = $kernel->getProjectDir().'/bin/console';
        $environment = $kernel->getEnvironment();
        $this->process = Process::fromShellCommandline("exec php $executable app:probe:worker --env=$environment");
        $this->input = new InputStream();

        $this->process->setInput($this->input);
        $this->process->setTimeout($timeout);
        $this->process->setIdleTimeout($idleTimeout);
    }

    public function start(): void
    {
        $this->logger->info('Starting new worker.');

        $this->process->start(function ($type, $data) {
            if ($type === Process::OUT) {
                $this->receiveBuffer .= $data;
                if (json_decode($this->receiveBuffer, true)) {
                    $callback = $this->callback;
                    $callback($type, $this->receiveBuffer);
                    $this->release();
                }
            }

            if ($type === Process::ERR && is_callable($this->callback)) {
                $callback = $this->callback;
                $callback($type, $data);
            }
        });

        $this->name = '#'.$this->process->getPid();
    }

    public function stop(): void
    {
        $this->process->stop(3, SIGINT);
        $this->receiveBuffer = null;
        $this->startTime = null;
        $this->expectedRuntime = null;
        $this->executing = false;
    }

    public function release(): void
    {
        $this->receiveBuffer = '';
        $this->startTime = null;
        $this->expectedRuntime = null;
        $this->executing = false;
        $this->manager->release($this);
    }

    public function send($data, $expectedRuntime, callable $callback): void
    {
        $this->executing = true;
        $this->startTime = microtime(true);
        $this->expectedRuntime = $expectedRuntime;
        $this->callback = $callback;

        $this->lastTask = $data;
        $this->input->write($data);
    }

    public function loop(): void
    {
        if (null != $this->startTime && null != $this->expectedRuntime) {
            $actualRuntime = microtime(true) - $this->startTime;
            $expectedRuntime = $this->expectedRuntime * 1.25;
            if ($actualRuntime > $expectedRuntime) {
                throw new WorkerTimedOutException($expectedRuntime, $this->lastTask);
            }
        }
        if (!$this->executing) {
            $this->process->checkTimeout();
        }
        $this->process->getIncrementalOutput();
        $this->process->getIncrementalErrorOutput();
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }
}
