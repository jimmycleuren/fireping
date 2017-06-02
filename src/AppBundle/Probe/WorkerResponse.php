<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 1/06/2017
 * Time: 16:33
 */

namespace AppBundle\Probe;

class WorkerResponse
{
    protected $probe;
    protected $data = array();

    public function __construct(ProbeDefinition $probe, array $data)
    {
        $this->probe = $probe;
        // TODO: Validation that $results contains the necessary fields!
        // Should contain:
        // - timestamp
        // - array 'return':
        // -- ( 'ip', 'results' )
        // Alternatively, should be an object. :-)
        $this->data = $data;
    }

    public function __toString()
    {
        $probeId = $this->probe->getId();
        $probeType = $this->probe->getType();
        $timestamp = $this->data['timestamp'];

        $formatted = array(
            $probeId => array(
                'type' => $probeType,
                'timestamp' => $timestamp,
                'targets' => array(),
            ),
        );

        foreach ($this->data['return'] as $device) {
            $ipAddress = $device['ip'];
            $deviceId = $this->probe->getDeviceByIp($ipAddress);
            if ($deviceId) {
                $results = $device['result'];
                $formatted[$probeId]['targets'][$deviceId] = $results;
            }
        }

        return json_encode($formatted);
    }
}