<?php

namespace App\Controller\Admin;

use App\Entity\SlaveGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SlaveGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SlaveGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SlaveGroup')
            ->setEntityLabelInPlural('SlaveGroup')
            ->setSearchFields(['id', 'name'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $devices = AssociationField::new('devices');
        $domains = AssociationField::new('domains');
        $slaves = AssociationField::new('slaves');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $devices, $domains, $slaves];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $devices, $domains, $slaves];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $devices, $domains, $slaves];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $devices, $domains, $slaves];
        }
        return [];
    }
}
