<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 14:33
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Slave;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    /**
     * @param $id
     * @return array
     *
     * @Route("/api/slaves/{id}/result")
     * @Method("POST")
     *
     * Process new results from a slave
     */
    public function resultAction($id)
    {
        return new JsonResponse(array("code" => 200, "message" => "Results saved"));
    }
}