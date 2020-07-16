<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class PingParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('retries', IntegerType::class, [
                'required' => false,
                'row_attr' => ['class' => 'field-number']
            ])
            ->add('packetSize', IntegerType::class, [
                'required' => false,
                'label' => 'Packet Size',
                'row_attr' => ['class' => 'field-number']
            ]);
    }

    public function getParent()
    {
        return JsonParametersType::class;
    }
}
