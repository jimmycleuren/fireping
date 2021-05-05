<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

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
            ->setSearchFields(['id', 'username', 'roles', 'email'])
            ->setPaginatorPageSize(30)
            ->setDefaultSort(['username' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username');
        $email = TextField::new('email');
        $roles = ChoiceField::new('roles')
            ->setChoices(['ROLE_API' => 'ROLE_API', 'ROLE_ADMIN' => 'ROLE_ADMIN'])
            ->setFormTypeOption('multiple', true);
        $plainPassword = TextField::new('plainPassword')->setFormType(PasswordType::class);
        $id = IntegerField::new('id', 'ID');
        $enabled = BooleanField::new('enabled');
        $lastLogin = DateTimeField::new('lastLogin');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $email, $enabled, $lastLogin];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $roles, $email, $enabled, $lastLogin];
        }

        if (Crud::PAGE_NEW === $pageName) {
            return [$username, $email, $roles, $plainPassword, $enabled];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [$username, $email, $roles, $plainPassword, $enabled];
        }

        return [];
    }
}
