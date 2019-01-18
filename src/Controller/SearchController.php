<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    /**
     * @Route("/search")
     */
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
        $q = $request->get('q');

        if (!$q || $q == "") {
            return $this->redirect("/");
        }

        $searchDevices = $em->createQuery("
            SELECT d
            FROM App:Device d
            WHERE d.name LIKE '%".$q."%'
            OR d.ip LIKE '%".$q."%'
        ")->getResult();

        $searchDomains = $em->createQuery("
            SELECT d
            FROM App:Domain d
            WHERE d.name LIKE '%".$q."%'
        ")->getResult();

        $domains = $em->getRepository("App:Domain")->findBy(array('parent' => null), array('name' => 'ASC'));

        return $this->render('search/index.html.twig', array(
            'q' => $q,
            'domains' => $domains,
            'search_devices' => $searchDevices,
            'search_domains' => $searchDomains
        ));
    }
}
