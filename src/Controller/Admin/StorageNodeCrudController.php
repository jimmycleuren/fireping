<?php

namespace App\Controller\Admin;

use App\Entity\StorageNode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StorageNodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StorageNode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('StorageNode')
            ->setEntityLabelInPlural('StorageNode')
            ->setSearchFields(['id', 'name', 'ip', 'status'])
            ->setPaginatorPageSize(30)
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $ip = TextField::new('ip');
        $status = ChoiceField::new('status')->setChoices(['active' => 'active', 'inactive' => 'inactive']);
        $id = IntegerField::new('id', 'ID');
        $devices = AssociationField::new('devices');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $ip, $status, $devices];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $ip, $status, $devices];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $ip, $status];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $ip, $status];
        }

        return [];
    }
}
