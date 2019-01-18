<?php

namespace App\Controller;

use App\Entity\Domain;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DomainController extends AbstractController
{
    /**
     * @Route("/domain/{id}")
     * @ParamConverter("domain", class="App:Domain")
     */
    public function getAction(Domain $domain)
    {
        return $this->render('domain/view.html.twig', array(
            'domain' => $domain,
            'current_domain' => $domain
        ));
    }
}
