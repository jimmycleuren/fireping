<?php
declare(strict_types=1);

namespace App\Form\Type\AlertDestination;

use App\Form\Type\DynamicParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class SlackParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('channel', TextType::class, [
                'required' => true,
                'help' => 'Channel to send alerts to'
            ])
            ->add('url', UrlType::class, [
                'required' => true,
                'help' => 'Slack endpoint to send alerts to'
            ])
        ;
    }

    public function getParent()
    {
        return DynamicParametersType::class;
    }
}