<?php

namespace App\Controller\Admin;

use App\Entity\Domain;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DomainCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Domain::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Domain')
            ->setEntityLabelInPlural('Domain')
            ->setSearchFields(['id', 'name'])
            ->setPaginatorPageSize(30)
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $parent = AssociationField::new('parent')->formatValue(fn(?string $value, Domain $entity) => null === $value ? null : $entity->getParentFqdn());
        $slavegroups = AssociationField::new('slavegroups');
        $probes = AssociationField::new('probes');
        $alertRules = AssociationField::new('alertRules');
        $alertDestinations = AssociationField::new('alertDestinations');
        $id = IntegerField::new('id', 'ID');
        $devices = AssociationField::new('devices');
        $subdomains = AssociationField::new('subdomains');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $parent, $subdomains];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $parent, $slavegroups, $probes, $alertRules, $alertDestinations, $devices, $subdomains];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $parent, $slavegroups, $probes, $alertRules, $alertDestinations];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $parent, $slavegroups, $probes, $alertRules, $alertDestinations];
        }

        return [];
    }
}
