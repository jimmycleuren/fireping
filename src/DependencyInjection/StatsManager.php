<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 30/04/2020
 * Time: 8:45
 */

namespace App\DependencyInjection;

use App\Exception\WorkerTimedOutException;
use App\ShellCommand\GetConfigHttpWorkerCommand;
use App\ShellCommand\PostResultsHttpWorkerCommand;
use App\ShellCommand\PostStatsHttpWorkerCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class StatsManager
{
    private $successfulPosts = 0;
    private $failedPosts = 0;
    private $discardedPosts = 0;
    private $workers;
    private $queues;
    private $logger;

    public function __construct(LoggerInterface $logger){
        $this->logger = $logger;
    }

    public function getStats() {
        $res = [
            'load' => sys_getloadavg(),
            'memory' => $this->getMemoryUsage(),
            'ip' => gethostbyname(gethostname()),
            'posts' => [
                'success' => $this->successfulPosts,
                'failed' => $this->failedPosts,
                'discarded' => $this->discardedPosts
            ],
            'workers' => $this->workers,
            'queues' => $this->queues,
        ];

        $this->queues = [];
        $this->workers = [];
        $this->successfulPosts = 0;
        $this->failedPosts = 0;
        $this->discardedPosts = 0;

        return $res;
    }

    public function addSuccessfulPost() {
        $this->successfulPosts++;
    }

    public function addFailedPost() {
        $this->failedPosts++;
    }

    public function addDiscardedPost() {
        $this->discardedPosts++;
    }

    public function addQueueItems($id, $count) {
        if (!isset($this->queues[date("U")])) {
            $this->queues[date("U")] = [];
        }
        $this->queues[date("U")][$id] = $count;
    }

    public function addWorkerStats($total, $available, $types) {
        $temp = [
            'total' => $total,
            'available' => $available,
        ];

        foreach ($types as $type => $count) {
            switch ($type) {
                case PostStatsHttpWorkerCommand::class:
                    $name = "stats";
                    break;
                case GetConfigHttpWorkerCommand::class:
                    $name = "config";
                    break;
                case PostResultsHttpWorkerCommand::class:
                    $name = "results";
                    break;
                case "ping":
                case "queue":
                case "http":
                case "traceroute":
                    $name = $type;
                    break;
                default:
                    $this->logger->warning("Could not simplify the $type worker type");
                    $name = substr($type, -15);
                    break;
            }
            $temp[$name] = $count;
        }

        $this->workers[date("U")] = $temp;
    }

    private function getMemoryUsage() {
        $free = shell_exec('free');
        $free = trim($free);
        $free = explode("\n", $free);
        if (!isset($free[1])) {
            $this->logger->warning("'free' executable not found");
            return [];
        }
        $mem = explode(" ", $free[1]);
        unset($mem[0]);
        $mem = array_filter($mem); // removes nulls from array
        $mem = array_merge($mem); // puts arrays back to [0],[1],[2] after filter removes nulls

        return $mem;
    }
}