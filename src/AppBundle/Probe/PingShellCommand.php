<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 1/06/2017
 * Time: 9:58
 */

namespace AppBundle\Probe;

use Symfony\Component\Process\ExecutableFinder;

class PingShellCommand
{
    protected $interval = 1;
    protected $samples = 10;
    protected $targets = array();

    public function __construct(array $targets, $samples, $interval)
    {
        $this->targets = $targets;
        $this->samples = $samples;
        $this->interval = $interval * 1000;
    }

    public function execute()
    {
        // TODO: Implement __invoke() method.
        $finder = new ExecutableFinder();
        if (!$finder->find('fping')) {
            throw new \RuntimeException('fping is not installed.');
        }

        $targets = $this->transformTargets();
        $command = "fping -C $this->samples -p $this->interval -q $targets 2>&1";

        $out = '';
        exec($command, $out);

        return $out;
    }

    private function transformTargets()
    {
        return implode(" ", $this->targets);
    }
}