<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 20/06/2017
 * Time: 9:40
 */

namespace AppBundle\OutputFormatter;

class TracerouteOutputFormatter implements OutputFormatterInterface
{
    public function format($input)
    {
        $resultArray = array();
        foreach ($input as $result) {
            $output = array();
            preg_match(
                "/^\s*(?P<hop>\d+)(?:\s+(?P<hostname>[\w\.\-]+)\s+\((?P<ip>[\d\.]+)\))?(?P<result>(?:\s+(?:\d+[\.,]\d{3}\sms|\*))+)$/",
                $result,
                $matches
            );
            if (isset($matches['hop'])) {
                $output['hop'] = $matches['hop'];
            }
            if (isset($matches['hostname'])) {
                $output['hostname'] = $matches['hostname'];
            }
            if (isset($matches['ip'])) {
                $output['ip'] = $matches['ip'];
            }
            if (isset($matches['result'])) {
                $output['result'] = $this->transformResult($matches['result']);
            }
            if (count($output)) {
                $resultArray[] = $output;
            }
        }
        return $resultArray;
    }

    /**
     * @param string $input
     * @return array
     */
    protected function transformResult(string $input) : array
    {
        $input = str_replace('ms', '', $input);
        $input = trim($input);
        return preg_split('/\s+/', $input);
    }
}