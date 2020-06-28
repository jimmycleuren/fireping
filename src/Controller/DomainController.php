<?php

namespace App\Controller;

use App\Entity\Domain;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DomainController extends AbstractController
{
    /**
     * @Route("/domain/{id}")
     * @ParamConverter("domain", class="App:Domain")
     */
    public function getAction(Domain $domain, Request $request, ContainerInterface $container)
    {
        return $this->render('domain/view.html.twig', [
            'domain' => $domain,
            'current_domain' => $domain,
            'start' => $request->get('start') ?? ((int) date('U')) - 43200,
            'end' => $request->get('end'),
            'control_sidebar_extra' => [
                'navigation' => [
                    'icon' => 'far fa-clock',
                    'controller' => 'App\Controller\DomainController::sidebarAction',
                ],
            ],
        ]);
    }

    public function sidebarAction(Request $originalRequest)
    {
        return $this->render('domain/sidebar.html.twig', [
            'start' => $originalRequest->get('start') ?? ((int) date('U')) - 43200,
            'end' => $originalRequest->get('end'),
        ]);
    }
}
