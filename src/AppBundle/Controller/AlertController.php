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
    private $em = null;

    /**
     * @Route("/alerts")
     */
    public function indexAction(Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $domains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null));

        $alertDomains = array();
        $alerts = $this->em->getRepository("AppBundle:Alert")->findBy(array('active' => 1));
        foreach($alerts as $alert) {
            if (!isset($alertDomains[$alert->getDevice()->getRootDomain()->getId()])) {
                $alertDomains[$alert->getDevice()->getRootDomain()->getId()] = array('name' => $alert->getDevice()->getRootDomain()->getName(), 'alerts' => 0);
            }
            $alertDomains[$alert->getDevice()->getRootDomain()->getId()]['alerts']++;
        }

        return $this->render('alert/index.html.twig', array(
            'domains' => $domains,
            'alertDomains' => $alertDomains,
            'alerts' => $alerts,
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

        return $this->render('alert/domain.html.twig', array(
            'domains' => $domains,
            'domain' => $domain,
            'alertDomains' => $alertDomains,
        ));
    }
}
