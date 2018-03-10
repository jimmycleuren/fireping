<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 10/03/2018
 * Time: 21:16
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use AppBundle\Storage\RrdStorage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DeviceApiController extends Controller
{
    protected $storage;

    public function __construct($storageType, RrdStorage $rrdStorage)
    {
        if ($storageType == "rrd") {
            $this->storage = $rrdStorage;
        }
    }

    /**
     * @Route("/api/devices/{id}/status.json", name="api_devices_status")
     */
    public function statusAction(Device $device)
    {
        $selectedProbe = null;
        $probes = $device->getActiveProbes();
        foreach ($probes as $probe) {
            if ($probe->getType() == "ping") {
                $selectedProbe = $probe;
            }
        }

        if (!$selectedProbe) {
            return new JsonResponse(array('message' => 'No ping probe assigned'), 500);
        }

        $slavegroups = $device->getActiveSlaveGroups();

        if ($slavegroups->count() == 0) {
            return new JsonResponse(array('message' => 'No slavegroup assigned'), 500);
        }

        $loss = $this->storage->fetch($device, $selectedProbe, $slavegroups[0], date("U"), 'loss', 'AVERAGE');
        $median = $this->storage->fetch($device, $selectedProbe, $slavegroups[0], date("U"), 'median', 'AVERAGE');

        if ($loss === null) {
            $status = "unknown";
        } elseif ($loss == 0) {
            $status = "up";
        } elseif ($loss < 1) {
            $status = "warning";
        } else {
            $status = "down";
        }

        return new JsonResponse(array(
            'status' => $status,
            'loss' => $loss,
            'median' => $median,
        ));
    }
}