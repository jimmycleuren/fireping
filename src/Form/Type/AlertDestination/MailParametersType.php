<?php
declare(strict_types=1);

namespace App\Form\Type\AlertDestination;

use App\Form\Type\DynamicParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class MailParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipient', UrlType::class, [
                'required' => true,
                'help' => 'E-mail address to send alerts to.'
            ])
        ;
    }

    public function getParent()
    {
        return DynamicParametersType::class;
    }
}