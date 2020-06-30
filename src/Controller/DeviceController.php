<?php

namespace App\Controller;

use App\DependencyInjection\Helper;
use App\Entity\Device;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DeviceController extends AbstractController
{
    /**
     * @Route("/device/{id}")
     * @ParamConverter("device", class="App:Device")
     */
    public function getAction(Device $device, Request $request, Helper $helper)
    {
        return $this->render('device/view.html.twig', [
            'device' => $device,
            'helper' => $helper,
            'control_sidebar_extra' => [
                'navigation' => [
                    'icon' => 'far fa-clock',
                    'controller' => 'App\Controller\DeviceController::sidebarAction',
                ],
                'config' => [
                    'icon' => 'far fa-list-alt',
                    'controller' => 'App\Controller\DeviceController::sidebarConfigAction',
                ],
            ],
        ]);
    }

    public function sidebarAction(Request $originalRequest)
    {
        return $this->render('device/sidebar.html.twig', [
            'device' => $originalRequest->get('device'),
        ]);
    }

    public function sidebarConfigAction(Request $originalRequest)
    {
        return $this->render('device/sidebar-config.html.twig', [
            'device' => $originalRequest->get('device'),
        ]);
    }
}
