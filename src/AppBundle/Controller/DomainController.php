<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Domain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DomainController extends Controller
{
    /**
     * @Route("/domain/{id}")
     * @ParamConverter("domain", class="AppBundle:Domain")
     */
    public function getAction(Domain $domain)
    {
        return $this->render('domain/view.html.twig', array(
            'domain' => $domain,
            'current_domain' => $domain
        ));
    }
}
