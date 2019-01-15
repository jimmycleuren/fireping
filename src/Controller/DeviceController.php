<?php

namespace App\Controller;

use App\Entity\Device;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DeviceController extends AbstractController
{
    /**
     * @Route("/device/{id}")
     * @ParamConverter("device", class="App:Device")
     */
    public function getAction(Device $device, Request $request)
    {
        return $this->render('device/view.html.twig', array(
            'device' => $device,
            'current_domain' => $device->getDomain()
        ));
    }
}
