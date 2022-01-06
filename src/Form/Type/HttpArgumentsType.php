<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class HttpArgumentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host', TextType::class, [
                'required' => false,
                'help' => "Override the host header, default: hostname/ip from device"
            ])
            ->add('path', TextType::class, [
                'required' => false,
                'help' => "Override the path, default: /"
            ])
            ->add('protocol', ChoiceType::class, [
                'choices' => ['http' => 'http', 'https' => 'https'],
                'required' => false,
                'label' => 'Protocol',
            ]);
    }

    public function getParent(): ?string
    {
        return ProbeArgumentsType::class;
    }
}
