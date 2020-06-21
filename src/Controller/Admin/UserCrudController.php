<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserCrudController extends AbstractCrudController
{
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function hashPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if ($plainPassword === '') {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        $hashedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('User')
            ->setSearchFields(['id', 'username', 'roles', 'email', 'apiToken'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username');
        $email = TextField::new('email');
        $roles = ChoiceField::new('roles')->setChoices(['ROLE_USER' => 'ROLE_USER', 'ROLE_ADMIN' => 'ROLE_ADMIN'])->setFormTypeOption('multiple', true);
        $plainPassword = TextField::new('plainPassword')->setFormType(PasswordType::class);
        $id = IntegerField::new('id', 'ID');
        $password = TextField::new('password');
        $enabled = Field::new('enabled');
        $apiToken = TextField::new('apiToken');
        $lastLogin = DateTimeField::new('lastLogin');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $email, $enabled, $apiToken, $lastLogin];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $roles, $password, $email, $enabled, $apiToken, $lastLogin];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$username, $email, $roles, $plainPassword];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$username, $email, $roles, $plainPassword];
        }
        return [];
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}
