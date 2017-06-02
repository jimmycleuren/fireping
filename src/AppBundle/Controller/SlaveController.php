<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 14:33
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Slave;
use AppBundle\Storage\RrdStorage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SlaveController extends Controller
{
    /**
     * @param $id
     * @return JsonResponse
     *
     * @Method("GET")
     * @Route("/api/slaves/{id}/config")
     * @ParamConverter("slave", class="AppBundle:Slave")
     */
    public function configAction($slave)
    {
        $config = array();

        foreach($slave->getDomains() as $domain) {
            $this->getDomainDevices($domain, $config);
        }

        foreach($slave->getDevices() as $device) {
            $result[$device->getName()] = array(
                'ip' => $device->getIp(),
                'probes' => $this->getDeviceProbes($device, $config),
            );
        }

        return new JsonResponse($config);
    }

    /**
     * @param $id
     * @return array
     *
     * @Method("POST")
     * @Route("/api/slaves/{id}/result")
     * @ParamConverter("slave", class="AppBundle:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction($slave, Request $request)
    {
        try {
            $this->em = $this->container->get('doctrine')->getManager();
            $this->logger = $this->container->get('logger');

            $probeRepository = $this->em->getRepository("AppBundle:Probe");
            $deviceRepository = $this->em->getRepository("AppBundle:Device");

            $probes = json_decode($request->getContent());

            foreach ($probes as $probeId => $probeData) {
                $probe = $probeRepository->findOneById($probeId);
                $timestamp = $probeData->timestamp;
                $targets = $probeData->targets;

                foreach ($targets as $targetId => $targetData) {
                    $device = $deviceRepository->findOneById($targetId);
                    if (!$device) {
                        $this->logger->error("Slave sends data for device '$targetId' but it does not exist");
                        continue;
                    }
                    $this->logger->debug("Updating data for probe ".$probe->getType()." on ".$device->getName());
                    switch ($probe->getType()) {
                        case "ping":
                            $this->container->get('processor.ping')->storeResult($device, $probe, $timestamp, $targetData);
                            break;
                    }
                }
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('code' => 500, 'message' => $e->getMessage()), 500);
        }

        return new JsonResponse(array("code" => 200, "message" => "Results saved"));
    }

    private function getDomainDevices($domain, &$config)
    {
        foreach ($domain->getSubDomains() as $subdomain) {
            $this->getDomainDevices($subdomain, $config);
        }

        foreach ($domain->getDevices() as $device) {
            $this->getDeviceProbes($device, $config);
        }
    }

    private function getDeviceProbes($device, &$config)
    {
        foreach($device->getProbes() as $probe) {
            $config[$probe->getId()]['type'] = $probe->getType();
            $config[$probe->getId()]['step'] = $probe->getStep();
            $config[$probe->getId()]['samples'] = $probe->getSamples();
            $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
        }

        $parent = $device->getDomain();
        while($parent != null) {
            foreach($parent->getProbes() as $probe) {
                $config[$probe->getId()]['type'] = $probe->getType();
                $config[$probe->getId()]['step'] = $probe->getStep();
                $config[$probe->getId()]['samples'] = $probe->getSamples();
                $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
            }
            $parent = $parent->getParent();
        }
    }
}