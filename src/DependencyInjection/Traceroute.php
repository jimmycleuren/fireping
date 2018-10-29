<?php
namespace App\DependencyInjection;
use App\ShellCommand\PingShellCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;


/**
 * Class Traceroute
 * @package App\DependencyInjection
 */
class Traceroute
{
    private $maxHops = 30;
    private $delay = null;
    private $step = null;
    private $samples = null;
    private $targets = [];
    private $logger = null;

    public function __construct($args, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->delay = $args['delay_execution'];
        $this->step = $args['step'];
        $this->samples = $args['args']['samples'];
        $this->targets = $args['targets'];

        $finder = new ExecutableFinder();
        if (!$finder->find("fping")) {
            throw new \Exception("fping is not installed on this system.");
        }
    }

    public function execute()
    {
        usleep($this->delay * 1000);

        $this->logger->debug("Launching traceroute (step=$this->step, samples=$this->samples) on ".json_encode($this->targets));

        $ips = [];
        foreach ($this->targets as $target) {
            $ips[] = $target['ip'];
        }

        $temp = $this->trace($ips, $this->samples, $this->step);

        $result = array();
        foreach ($this->targets as $target) {
            $result[$target['id']] = $temp[$target['ip']];
        }

        $this->logger->debug("Traceroute result: ".json_encode($result));

        return $result;
    }

    public function trace(array $ips, $samples, $step)
    {
        $result = array();
        $active = array();

        foreach($ips as $ip) {
            $active[$ip] = true;
        }

        $merged = array();
        $start = microtime(true);

        //determine hops
        for($i = 1; $i < $this->maxHops && count($active) > 0; $i++) {
            $res = $this->exec("fping -H $i -C 1 ".implode(" ", array_keys($active))." 2>&1");
            $res = implode("\n", $res);
            //var_dump($res);
            foreach (array_keys($active) as $ip) {
                if (preg_match("/ICMP Time Exceeded from (?P<hop>[\d\.]+) for ICMP Echo sent to $ip/", $res, $matches)) {
                    $result[$ip]['hop'][$i]['ip'] = $matches["hop"];
                    $merged[] = $matches["hop"];
                } elseif (preg_match("/$ip([\s]+): (?P<latency>[\d\.]+)/", $res, $matches)) {
                    $result[$ip]['hop'][$i]['ip'] = $ip;
                    $merged[] = $ip;
                    unset($active[$ip]);
                } else {
                    $result[$ip]['hop'][$i]['ip'] = "*";
                }
            }
        }

        $end = microtime(true);
        $remaining = $step - ($end - $start);

        $this->logger->info("Trace took ".($end - $start)." seconds, ".$remaining." seconds remaining");

        $waitTime = floor($remaining / $samples) * 1000;

        //ping all gathered hops with the given waitTime and samples
        $merged = array_unique($merged);
        $latencies = array();
        $res = $this->exec("fping -C $samples -p ".($waitTime)." ".implode(" ", $merged)." 2>&1");
        $res = implode("\n", $res);

        foreach ($merged as $ip) {
            if (preg_match("/$ip([\s]+): (?P<latencies>[\d\.\-\ ]+)/", $res, $matches)) {
                $latencies[$ip] = explode(" ", str_replace("-", "-1", $matches['latencies']));
            }
        }

        foreach ($result as $ip => $data) {
            foreach ($data['hop'] as $key => $hop) {
                if ($hop['ip'] != "*") {
                    $result[$ip]['hop'][$key]['latencies'] = $latencies[$hop['ip']];
                }
            }
        }

        return $result;
    }

    private function exec($command)
    {
        $out = '';
        exec($command, $out);

        return $out;
    }
}