<?php

namespace App\Controller\Admin;

use App\Entity\AlertRule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AlertRuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlertRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AlertRule')
            ->setEntityLabelInPlural('AlertRule')
            ->setSearchFields(['id', 'name', 'datasource', 'pattern', 'messageUp', 'messageDown'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $datasource = TextField::new('datasource');
        $pattern = TextField::new('pattern');
        $messageUp = TextField::new('messageUp');
        $messageDown = TextField::new('messageDown');
        $probe = AssociationField::new('probe');
        $parent = AssociationField::new('parent');
        $children = AssociationField::new('children');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $datasource, $pattern, $messageUp, $messageDown, $probe];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $datasource, $pattern, $messageUp, $messageDown, $probe, $parent, $children];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $datasource, $pattern, $messageUp, $messageDown, $probe, $parent, $children];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $datasource, $pattern, $messageUp, $messageDown, $probe, $parent, $children];
        }
        return [];
    }
}
