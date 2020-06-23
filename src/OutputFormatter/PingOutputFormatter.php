<?php

namespace App\OutputFormatter;


class PingOutputFormatter implements OutputFormatterInterface
{
    public function format($input) : array
    {
        $output = array();
        foreach ($input as $target) {
            $parsed = $this->parseInput($target);
            if (!empty($parsed)) {
                $output[] = $parsed;
            }
        }
        return $output;
    }

    private function parseInput($input) : array
    {
        $output = array();
        preg_match(
            "/^(?P<ip>(?:[\d]{1,3}\.){3}[\d]{1,3})\s+:\s+(?P<result>[\d\.\-\s]+)$/",
            $input,
            $matches
        );
        if (isset($matches['ip'])) {
            $output['ip'] = $matches['ip'];
        }
        if (isset($matches['result'])) {
            $output['result'] = $this->transformResult($matches['result']);
        }
        return $output;
    }

    private function transformResult($result)
    {
        $dashes = str_replace("-", "-1", $result);
        return explode(" ", $dashes);
    }
}