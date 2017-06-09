<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 13:05
 */

namespace AppBundle\OutputFormatter;


class PingOutputFormatter implements OutputFormatterInterface
{
    public function format($input) : array
    {
        $output = array();
        foreach ($input as $target) {
            $output[] = $this->parseInput($target);
        }
        return $output;
    }

    private function parseInput($input) : array
    {
        list ($ip, $result) = explode(' : ', $input);
        $sub = array(
            'ip' => trim($ip),
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