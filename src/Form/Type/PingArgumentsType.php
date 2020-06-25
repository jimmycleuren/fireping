<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class PingArgumentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('retries', NumberType::class, ['required' => false])
            ->add('packetSize', NumberType::class, ['required' => false, 'label' => 'Packet Size'])
        ;
    }
}