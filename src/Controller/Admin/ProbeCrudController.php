<?php

namespace App\Controller\Admin;

use App\Admin\Field\ProbeArgumentsField;
use App\Entity\Probe;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProbeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Probe::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Probe')
            ->setEntityLabelInPlural('Probe')
            ->setSearchFields(['id', 'name', 'type', 'step', 'samples', 'arguments'])
            ->setPaginatorPageSize(30)
            ->setFormThemes(['admin/crud/probe/_fields.html.twig', '@EasyAdmin/crud/form_theme.html.twig']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = TextField::new('type');
        $step = IntegerField::new('step');
        $samples = IntegerField::new('samples');
        $archives = AssociationField::new('archives');
        $id = IntegerField::new('id', 'ID');
        $arguments = ProbeArgumentsField::new('arguments');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $step, $samples, $archives];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $step, $samples, $arguments, $archives];
        }

        if (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $step, $samples, $archives, FormField::addPanel('Arguments'), $arguments];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [$name, FormField::addPanel('Arguments'), $arguments];
        }

        return [];
    }
}
