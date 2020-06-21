<?php

namespace App\Controller\Admin;

use App\Entity\Device;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DeviceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Device::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Device')
            ->setEntityLabelInPlural('Device')
            ->setSearchFields(['id', 'name', 'ip'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $domain = AssociationField::new('domain');
        $ip = TextField::new('ip');
        $slavegroups = AssociationField::new('slavegroups');
        $probes = AssociationField::new('probes');
        $alertRules = AssociationField::new('alertRules');
        $alertDestinations = AssociationField::new('alertDestinations');
        $id = IntegerField::new('id', 'ID');
        $alerts = AssociationField::new('alerts');
        $storageNode = AssociationField::new('storageNode');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $ip, $domain];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $ip, $domain, $slavegroups, $probes, $alertRules, $alertDestinations, $alerts, $storageNode];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $domain, $ip, $slavegroups, $probes, $alertRules, $alertDestinations];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $domain, $ip, $slavegroups, $probes, $alertRules, $alertDestinations];
        }
        return [];
    }
}
