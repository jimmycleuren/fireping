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
            list ($ip, $result) = explode(' : ', $target);
            $sub = array(
                "ip" => $ip,
                "result" => $this->transformResult($result),
            );
            $output[] = $sub;
        }
        return $output;
    }

    public function transformResult($result)
    {
        $dashes = str_replace("-", "-1", $result);
        return explode(" ", $dashes);
    }
}