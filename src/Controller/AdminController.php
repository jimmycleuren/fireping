<?php

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;

class AdminController extends EasyAdminController
{
    public function persistUserEntity($user)
    {
        parent::persistEntity($user);
    }

    public function updateUserEntity($user)
    {
        parent::updateEntity($user);
    }

}