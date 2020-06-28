<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Domain;
use App\Repository\AlertRepository;
use App\Repository\DomainRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/alerts")
     */
    public function indexAction(AlertRepository $alertRepository)
    {
        $alertDomains = [];
        $alerts = $alertRepository->findBy(['active' => 1]);
        foreach ($alerts as $alert) {
            if (!isset($alertDomains[$alert->getDevice()->getRootDomain()->getId()])) {
                $alertDomains[$alert->getDevice()->getRootDomain()->getId()] = ['name' => $alert->getDevice()->getRootDomain()->getName(), 'alerts' => 0];
            }
            ++$alertDomains[$alert->getDevice()->getRootDomain()->getId()]['alerts'];
        }

        return $this->render('alert/index.html.twig', [
            'alertDomains' => $alertDomains,
            'alerts' => $alerts,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/alerts/{id}")
     */
    public function detailAction(Alert $alert)
    {
        return new JsonResponse();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/alerts/domain/{id}")
     * @ParamConverter("domain", class="App:Domain")
     */
    public function domainAction(Domain $domain, DomainRepository $domainRepository)
    {
        $alertDomains = $domainRepository->findBy(['parent' => $domain]);

        return $this->render('alert/domain.html.twig', [
            'domain' => $domain,
            'alertDomains' => $alertDomains,
        ]);
    }
}
