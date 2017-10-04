<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use AppBundle\Entity\Domain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class AlertController extends Controller
{
    /**
     * @Route("/alerts")
     */
    public function indexAction(Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $domains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null));

        return $this->render('alert/index.html.twig', array(
            'domains' => $domains,
            'alertDomains' => $domains,
        ));
    }

    /**
     * @Route("/alerts/domain/{id}")
     * @ParamConverter("domain", class="AppBundle:Domain")
     */
    public function domainAction(Domain $domain, Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $domains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null));
        $alertDomains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => $domain));

        return $this->render('alert/index.html.twig', array(
            'domains' => $domains,
            'domain' => $domain,
            'alertDomains' => $alertDomains,
        ));
    }
}
