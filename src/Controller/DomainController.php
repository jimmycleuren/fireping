<?php

namespace App\Controller;

use App\Entity\Domain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DomainController extends AbstractController
{
    /**
     * @Route("/domain/{id}")
     * @ParamConverter("domain", class="App:Domain")
     */
    public function getAction(Domain $domain, Request $request)
    {
        return $this->render('domain/view.html.twig', array(
            'domain' => $domain,
            'current_domain' => $domain,
            'start' => $request->get('start') ?? ((int)date("U")) - 43200,
            'end' => $request->get('end')
        ));
    }
}
