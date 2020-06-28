<?php

namespace App\OutputFormatter;

class PingOutputFormatter implements OutputFormatterInterface
{
    public function format($input): array
    {
        $output = [];
        foreach ($input as $target) {
            $parsed = $this->parseInput($target);
            if (!empty($parsed)) {
                $output[] = $parsed;
            }
        }

        return $output;
    }

    private function parseInput(string $input): array
    {
        if (false === strpos($input, ':')) {
            return [];
        }

        [$hostname, $results] = explode(':', $input);

        return ['ip' => trim($hostname), 'result' => $this->transformResult(trim($results))];
    }

    private function transformResult($result)
    {
        return explode(' ', str_replace('-', '-1', $result));
    }
}
