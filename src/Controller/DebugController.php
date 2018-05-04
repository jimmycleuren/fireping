<?php

namespace App\Controller;

use App\Entity\Device;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DebugController extends Controller
{
    /**
     * @Route("/debug", name="debug")
     */
    public function getAction(Request $request)
    {
        $debug = !$this->container->get('session')->get('debug');
        $this->container->get('session')->set('debug', $debug);

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }
}
