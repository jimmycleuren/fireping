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
        return $this->render('device/view.html.twig', array(
            'device' => $device,
            'current_domain' => $device->getDomain()
        ));
    }
}
