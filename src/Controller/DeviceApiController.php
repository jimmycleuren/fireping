<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 10/03/2018
 * Time: 21:16
 */

namespace App\Controller;

use App\Entity\Device;
use App\Storage\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DeviceApiController extends AbstractController
{
    /**
     * @Route("/api/devices/{id}/status.json", name="api_devices_status")
     */
    public function statusAction(Device $device, Cache $cache)
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

        $loss = $cache->fetch($device, $selectedProbe, $slavegroups[0], 'loss');
        $median = $cache->fetch($device, $selectedProbe, $slavegroups[0], 'median');

        if ($median == "U") {
            $status = "down";
        } elseif ($median > 0) {
            $status = "up";
        } else {
            $status = "unknown";
        }

        return new JsonResponse(array(
            'status' => $status,
            'loss' => $loss,
            'median' => $median,
        ));
    }
}