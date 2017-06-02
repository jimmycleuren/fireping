<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 1/06/2017
 * Time: 13:53
 */

namespace AppBundle\Probe;


class PingResponseFormatter
{
    public function __construct()
    {

    }

    public function format($input)
    {
        $output = array();
        foreach ($input as $target) {
            $output[] = $this->parseInput($target);
        }
        return $output;
    }

    private function parseInput($input)
    {
        list ($ip, $result) = explode(' : ', $input);
        $sub = array(
            'ip' => $ip,
            'result' => $this->transformResult($result),
        );
        return $sub;
    }

    private function transformResult($result)
    {
        $dashes = str_replace("-", "-1", $result);
        return explode(" ", $dashes);
    }
}