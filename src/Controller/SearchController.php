<?php

namespace App\Controller;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /**
     * @Route("/search")
     */
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
        $q = $request->get('q');

        if (!$q || '' == $q) {
            return $this->redirect('/');
        }

        $searchDevices = $em->createQuery("
            SELECT d
            FROM App:Device d
            WHERE d.name LIKE :q
            OR d.ip LIKE :q
            ORDER BY d.name ASC
        ")
            ->setParameter('q', '%'.$q.'%')
            ->getResult();

        $searchDomains = $em->createQuery("
            SELECT d
            FROM App:Domain d
            WHERE d.name LIKE :q
            ORDER BY d.name ASC
        ")
            ->setParameter('q', '%'.$q.'%')
            ->getResult();

        $domains = $em->getRepository(Domain::class)->findBy(['parent' => null], ['name' => 'ASC']);

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'domains' => $domains,
            'search_devices' => $searchDevices,
            'search_domains' => $searchDomains,
        ]);
    }
}
