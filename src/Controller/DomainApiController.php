<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 10/03/2018
 * Time: 21:16
 */

namespace App\Controller;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DomainApiController extends AbstractController
{
    /**
     * @Route("/api/domains/{id}/alerts.json", name="api_domains_alerts")
     */
    public function alertsAction(Domain $domain, EntityManagerInterface $entityManager)
    {
        if (extension_loaded ('newrelic')) {
            newrelic_name_transaction("api_domains_alerts");
        }

        $devices = $this->getDevices($domain);

        $alerts = $entityManager
            ->createQuery("SELECT a FROM App:Alert a WHERE a.active = 1 AND a.device IN (:devices)")
            ->setParameter("devices", $devices)
            ->getResult();

        $result = array();
        foreach($alerts as $alert) {
            $result[] = array(
                'message' => $alert->__toString(),
                'device' => array(
                    'id' => $alert->getDevice()->getId(),
                    'name' => $alert->getDevice()->getName(),
                ),
                'alertRule' => array(
                    'id' => $alert->getAlertRule()->getId(),
                    'name' => $alert->getAlertRule()->getName()
                ),
                'firstseen' => $alert->getFirstSeen(),
                'lastseen' => $alert->getLastSeen()
            );
        }

        return new JsonResponse($result);
    }

    private function getDevices(Domain $domain)
    {
        $devices = $domain->getDevices()->toArray();

        foreach ($domain->getSubdomains() as $subdomain) {
            $devices = array_merge($devices, $this->getDevices($subdomain));
        }

        return $devices;
    }
}