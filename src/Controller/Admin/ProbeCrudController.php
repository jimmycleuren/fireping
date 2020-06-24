<?php

namespace App\Controller\Admin;

use App\Entity\Probe;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = ChoiceField::new('type')->setChoices(['ping' => 'ping', 'traceroute' => 'traceroute', 'http' => 'http']);
        $step = IntegerField::new('step');
        $samples = IntegerField::new('samples');
        $arguments = TextareaField::new('arguments');
        $archives = AssociationField::new('archives');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $step, $samples, $archives];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $step, $samples, $arguments, $archives];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $step, $samples, $arguments];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $type, $step, $samples, $arguments];
        }
        return [];
    }
}
