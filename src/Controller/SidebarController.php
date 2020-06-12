<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SidebarController extends AbstractController
{
    public function settingsAction(Request $originalRequest)
    {
        return $this->render("sidebar/settings.html.twig");
    }
}