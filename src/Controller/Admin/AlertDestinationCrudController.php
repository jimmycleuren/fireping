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
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $type = ChoiceField::new('type')->setChoices([
            'Slack'   => AlertDestination::TYPE_SLACK,
            'Logs'    => AlertDestination::TYPE_LOG,
            'Webhook' => AlertDestination::TYPE_HTTP,
            'E-mail'  => AlertDestination::TYPE_MAIL,
        ]);
        $id = IntegerField::new('id', 'ID');
        $parameters = DynamicParametersField::new('parameters');

        $typeRo = clone $type;
        $typeRo->setFormTypeOption('disabled', true);

        $map = [
            Crud::PAGE_INDEX => [$id, $name, $type],
            Crud::PAGE_DETAIL => [$id, $name, $type, $parameters],
            Crud::PAGE_NEW => [$name, $type],
            Crud::PAGE_EDIT => [$name, $typeRo, FormField::addPanel('Parameters'), $parameters]
        ];

        return $map[$pageName];
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $map = [
            AlertDestination::TYPE_SLACK => SlackParametersType::class,
            AlertDestination::TYPE_MAIL => MailParametersType::class,
            AlertDestination::TYPE_HTTP => HttpParametersType::class,
            AlertDestination::TYPE_LOG => MonologParametersType::class
        ];

        $entityDto->getFields()->get('parameters')->setFormType($map[$entityDto->getInstance()->getType()]);

        return parent::createEditForm($entityDto, $formOptions, $context);
    }
}
