<?php

namespace App\Controller\Admin;

use App\Entity\ProbeArchive;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProbeArchiveCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProbeArchive::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('ProbeArchive')
            ->setEntityLabelInPlural('ProbeArchive')
            ->setSearchFields(['id', 'function', 'steps', 'rows'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $function = TextField::new('function');
        $steps = IntegerField::new('steps');
        $rows = IntegerField::new('rows');
        $probe = AssociationField::new('probe');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $function, $steps, $rows, $probe];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $function, $steps, $rows, $probe];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$function, $steps, $rows, $probe];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$function, $steps, $rows, $probe];
        }
        return [];
    }
}
