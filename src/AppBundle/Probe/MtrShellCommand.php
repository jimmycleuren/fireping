<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 1/06/2017
 * Time: 9:58
 */

namespace AppBundle\Probe;

use Symfony\Component\Process\ExecutableFinder;

class MtrShellCommand
{
    protected $interval;
    protected $samples;
    protected $targets = array();

    public function __construct(array $targets, $samples = 10, $interval = 1)
    {
        $this->targets = $targets;
        $this->samples = $samples;
        $this->interval = $interval;
    }

    public function execute()
    {
        // TODO: Implement __invoke() method.
        $finder = new ExecutableFinder();
        if (!$finder->find('mtr')) {
            throw new \RuntimeException('mtr is not installed.');
        }

        $targets = $this->transformTargets();
        $command = "mtr -n -c $this->samples $targets --json 2>&1";

        $out = array();
        exec($command, $out);

        $out = implode("\n", $out);
        $decoded = json_decode($out, true);

        return $decoded;
    }

    private function transformTargets()
    {
        return $this->targets[0];
    }
}