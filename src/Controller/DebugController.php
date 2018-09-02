<?php

namespace App\Controller;

use App\Entity\Device;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DebugController extends Controller
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
