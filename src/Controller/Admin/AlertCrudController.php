<?php

namespace App\Controller\Admin;

use App\Entity\Alert;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class AlertCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Alert::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Alert')
            ->setEntityLabelInPlural('Alert')
            ->setSearchFields(['id', 'active'])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $active = IntegerField::new('active');
        $firstseen = DateTimeField::new('firstseen');
        $lastseen = DateTimeField::new('lastseen');
        $device = AssociationField::new('device');
        $alertRule = AssociationField::new('alertRule');
        $slaveGroup = AssociationField::new('slaveGroup');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $active, $firstseen, $lastseen, $device, $alertRule, $slaveGroup];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $active, $firstseen, $lastseen, $device, $alertRule, $slaveGroup];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$active, $firstseen, $lastseen, $device, $alertRule, $slaveGroup];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$active, $firstseen, $lastseen, $device, $alertRule, $slaveGroup];
        }
        return [];
    }
}
