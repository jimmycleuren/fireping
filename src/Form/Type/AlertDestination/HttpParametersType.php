<?php
declare(strict_types=1);

namespace App\Form\Type\AlertDestination;

use App\Form\Type\JsonParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class HttpParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', UrlType::class, [
                'required' => true,
                'help' => 'Endpoint to send alerts to.'
            ])
        ;
    }

    public function getParent()
    {
        return JsonParametersType::class;
    }
}
