<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('User')
            ->setSearchFields(['id', 'username', 'roles', 'email', 'apiToken'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username');
        $email = TextField::new('email');
        $roles = TextField::new('roles');
        $plainPassword = Field::new('plainPassword');
        $id = IntegerField::new('id', 'ID');
        $password = TextField::new('password');
        $enabled = Field::new('enabled');
        $apiToken = TextField::new('apiToken');
        $lastLogin = DateTimeField::new('lastLogin');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $email, $enabled, $apiToken, $lastLogin];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $roles, $password, $email, $enabled, $apiToken, $lastLogin];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$username, $email, $roles, $plainPassword];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$username, $email, $roles, $plainPassword];
        }
    }
}
