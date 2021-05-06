<?php

namespace App\Controller\API;

use App\Entity\Device;
use App\Storage\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DeviceController extends AbstractController
{
    /**
     * @Route("/api/devices/{id}/status.json", name="api_devices_status")
     */
    public function statusAction(Device $device, Cache $cache)
    {
        $selectedProbe = null;
        $probes = $device->getActiveProbes();
        foreach ($probes as $probe) {
            if ('ping' == $probe->getType()) {
                $selectedProbe = $probe;
            }
        }

        if (!$selectedProbe) {
            return new JsonResponse(['message' => 'No ping probe assigned'], 500);
        }

        $slavegroups = $device->getActiveSlaveGroups();

        if (0 == $slavegroups->count()) {
            return new JsonResponse(['message' => 'No slavegroup assigned'], 500);
        }

        $loss = $cache->fetch($device, $selectedProbe, $slavegroups[0], 'loss');
        $median = $cache->fetch($device, $selectedProbe, $slavegroups[0], 'median');

        if ('U' == $median) {
            $status = 'down';
        } elseif ($median > 0) {
            $status = 'up';
        } else {
            $status = 'unknown';
        }

        return new JsonResponse([
            'status' => $status,
            'loss' => $loss,
            'median' => $median,
        ]);
    }
}
