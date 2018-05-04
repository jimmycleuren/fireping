<?php
namespace App\DependencyInjection;
use App\ShellCommand\PingShellCommand;
use Symfony\Component\Process\ExecutableFinder;


/**
 * Class Traceroute
 * @package App\DependencyInjection
 */
class Traceroute
{
    private $maxHops = 30;

    public function __construct()
    {
        $finder = new ExecutableFinder();
        if (!$finder->find("fping")) {
            throw new \Exception("fping is not installed on this system.");
        }
    }

    public function execute()
    {
        $this->trace(array("8.8.8.8"), 1, 1);
    }

    public function trace(array $ips, $samples, $step)
    {
        $result = array();
        $active = array();

        foreach($ips as $ip) {
            $active[$ip] = true;
        }

        $merged = array();

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

        //ping all gathered hops with the given step and samples
        $merged = array_unique($merged);
        $latencies = array();
        $res = $this->exec("fping -C $samples -p ".($step * 1000 / $samples)." ".implode(" ", $merged)." 2>&1");
        $res = implode("\n", $res);

        foreach ($merged as $ip) {
            if (preg_match("/$ip([\s]+): (?P<latencies>[\d\.\-\ ]+)/", $res, $matches)) {
                $latencies[$ip] = explode(" ", $matches['latencies']);
            }
        }

        foreach ($result as $ip => $data) {
            foreach ($data['hop'] as $key => $hop) {
                if ($hop['ip'] != "*") {
                    $result[$ip]['hop'][$key]['latencies'] = $latencies[$hop['ip']];
                }
            }
        }

        var_dump($result);
    }

    private function exec($command)
    {
        $out = '';
        exec($command, $out);

        return $out;
    }
}