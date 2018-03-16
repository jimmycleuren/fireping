<?php

namespace AppBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;

class MenuExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGlobals()
    {
        return array(
            'menu' => $this->em->getRepository("AppBundle:Domain")->findBy(array('parent' => null), array('name' => 'ASC')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_Function('menuActive', function($domain, $currentDomain) {
                while($currentDomain != null) {
                    if($currentDomain->getId() == $domain->getId()) {
                        return true;
                    }
                    $currentDomain = $currentDomain->getParent();
                }
                return false;
            }),
        );
    }

    public function getName()
    {
        return "AppBundle:MenuExtension";
    }
}