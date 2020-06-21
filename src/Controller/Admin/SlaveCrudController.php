<?php

namespace App\Controller\Admin;

use App\Entity\Slave;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SlaveCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slave::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Slave')
            ->setEntityLabelInPlural('Slave')
            ->setSearchFields(['id', 'ip'])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $slavegroup = AssociationField::new('slavegroup');
        $id = TextField::new('id', 'ID');
        $lastContact = DateTimeField::new('lastContact');
        $ip = TextField::new('ip');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $lastContact, $ip, $slavegroup];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $lastContact, $ip, $slavegroup];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$slavegroup];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$slavegroup];
        }
        return [];
    }
}
