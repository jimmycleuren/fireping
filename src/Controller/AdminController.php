<?php

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
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