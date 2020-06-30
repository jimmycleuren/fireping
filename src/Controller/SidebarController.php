<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SidebarController extends AbstractController
{
    public function settingsAction(Request $originalRequest)
    {
        return $this->render('sidebar/settings.html.twig');
    }
}
