<?php

namespace App\Controller\Admin;

use App\Admin\Field\DynamicParametersField;
use App\Entity\AlertDestination;
use App\Form\Type\AlertDestination\HttpParametersType;
use App\Form\Type\AlertDestination\MailParametersType;
use App\Form\Type\AlertDestination\MonologParametersType;
use App\Form\Type\AlertDestination\SlackParametersType;
use App\Form\Type\DynamicParametersType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormInterface;

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
            ->setFormThemes(['admin/crud/_dynamic_parameters_field.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = ChoiceField::new('type')->setChoices([
            'Slack'   => 'slack',
            'Logs'    => 'monolog',
            'Webhook' => 'http',
            'E-mail'  => 'mail'
        ]);
        $id = IntegerField::new('id', 'ID');
        $parameters = DynamicParametersField::new('parameters');

        switch ($pageName) {
            case Crud::PAGE_INDEX:
                return [$id, $name, $type];
            case Crud::PAGE_DETAIL:
                return [$id, $name, $type, $parameters];
            case Crud::PAGE_NEW:
                return [$name, $type];
            case Crud::PAGE_EDIT:
                $typeRo = clone $type;
                $typeRo->setFormTypeOption('disabled', true);
                return [$name, $typeRo, FormField::addPanel('Parameters'), $parameters];
            default:
                return [];
        }
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $type = $entityDto->getInstance()->getType();

        $arguments = $entityDto->getFields()->get('parameters');
        switch ($type) {
            case 'slack':
                $arguments->setFormType(SlackParametersType::class);
                break;
            case 'mail':
                $arguments->setFormType(MailParametersType::class);
                break;
            case 'http':
                $arguments->setFormType(HttpParametersType::class);
                break;
            case 'monolog':
                $arguments->setFormType(MonologParametersType::class);
                break;
            default:
                $arguments->setFormType(DynamicParametersType::class);
        }

        return parent::createEditForm($entityDto, $formOptions, $context);
    }
}
