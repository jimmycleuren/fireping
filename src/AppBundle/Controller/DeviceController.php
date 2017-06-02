<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DeviceController extends Controller
{
    /**
     * @Route("/device/{id}")
     * @ParamConverter("device", class="AppBundle:Device")
     */
    public function getAction(Device $device, Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $domains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null));

        return $this->render('device/view.html.twig', array(
            'domains' => $domains,
            'device' => $device,
            'current_domain' => $device->getDomain()
        ));
    }
}
