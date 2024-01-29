<?php

namespace App\Slave\OutputFormatter;

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
        // TODO: This is indicative of an issue, but unfortunately I can no longer recall what prompted this.
        //       We should probably capture this input and send it to the master in some way to create alerts.
        if (false === strpos($input, ':')) {
            return [];
        }
        
        // TODO: These should not just be silently discarded.
        //       This is indicative of an issue, and the AlertDestination of the Device should be notified.
        //       Note: we should probably make it configurable to notify for these issues or not.
        //       Note: for this type of error, the left-hand side of the colon still contains the IP address,
        //             making it possible to create alerts for it.
        if (false !== strpos($input, ': duplicate')) {
            return [];
        }

        [$hostname, $results] = explode(' : ', $input);

        return ['ip' => trim($hostname), 'result' => $this->transformResult(trim($results))];
    }

    private function transformResult($result)
    {
        return explode(' ', str_replace('-', '-1', $result));
    }
}
