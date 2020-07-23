<?php

namespace App\Controller\Admin;

use App\Entity\AlertDestination\AlertDestination;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AlertDestinationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlertDestination::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AlertDestination')
            ->setEntityLabelInPlural('AlertDestination')
            ->setSearchFields(['id', 'name', 'type', 'parameters'])
            ->setPaginatorPageSize(30)
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = TextField::new('type');
        $id = IntegerField::new('id', 'ID');
        $parameters = TextareaField::new('parameters');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $parameters];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $parameters];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $type, $parameters];
        }

        return [];
    }
}
