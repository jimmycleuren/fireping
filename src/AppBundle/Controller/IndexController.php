<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends Controller
{
    private $em = null;

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $domains = $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null), array('name' => 'ASC'));

        return $this->render('default/index.html.twig', array(
            'domains' => $domains
        ));
    }
}
