<?php

namespace App\Controller\Admin;

use App\Admin\Field\JsonParametersField;
use App\Entity\Probe;
use App\Form\Type\HttpParametersType;
use App\Form\Type\PingParametersType;
use App\Form\Type\JsonParametersType;
use App\Form\Type\TracerouteParametersType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormInterface;

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
            ->setSearchFields(['id', 'name', 'type', 'step', 'samples'])
            ->setPaginatorPageSize(30)
            ->setFormThemes(['admin/crud/json_parameters/_fields.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = ChoiceField::new('type')->setChoices(['ping' => 'ping', 'traceroute' => 'traceroute', 'http' => 'http']);
        $step = IntegerField::new('step');
        $samples = IntegerField::new('samples');
        $archives = AssociationField::new('archives');
        $id = IntegerField::new('id', 'ID');
        $arguments = JsonParametersField::new('arguments');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $step, $samples, $archives];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $step, $samples, $arguments, $archives];
        }

        if (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $step, $samples];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [$name, FormField::addPanel('Arguments'), $arguments];
        }

        return [];
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $type = $entityDto->getInstance()->getType();

        $arguments = $entityDto->getFields()->get('arguments');
        switch ($type) {
            case 'ping':
                $arguments->setFormType(PingParametersType::class);
                break;
            case 'traceroute':
                $arguments->setFormType(TracerouteParametersType::class);
                break;
            case 'http':
                $arguments->setFormType(HttpParametersType::class);
                break;
            default:
                $arguments->setFormType(JsonParametersType::class);
        }

        return parent::createEditForm($entityDto, $formOptions, $context);
    }
}
