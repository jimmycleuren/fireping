<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    /**
     * @Route("/debug", name="debug")
     */
    public function getAction(Request $request, SessionInterface $session)
    {
        $debug = !$session->get('debug');
        $session->set('debug', $debug);

        $referer = $request->headers->get('referer');

        return new RedirectResponse($referer);
    }
}
