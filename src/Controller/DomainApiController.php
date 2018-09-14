<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 10/03/2018
 * Time: 21:16
 */

namespace App\Controller;

use ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer;
use ApiPlatform\Core\Serializer\JsonEncoder;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DomainApiController extends Controller
{
    /**
     * @Route("/api/domains/{id}/alerts.json", name="api_domains_alerts")
     */
    public function alertsAction(Domain $domain, EntityManagerInterface $entityManager)
    {
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