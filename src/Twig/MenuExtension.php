<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGlobals()
    {
        return array(
            'menu' => $this->em->getRepository("App:Domain")->findBy(array('parent' => null), array('name' => 'ASC')),
        );
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('menuActive', function($domain, $currentDomain) {
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
        return "App:MenuExtension";
    }
}