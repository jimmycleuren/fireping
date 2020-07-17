<?php
declare(strict_types=1);

namespace App\Form\Type\Probe;

use App\Form\Type\DynamicParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TracerouteParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Construct your arguments form here.
    }

    public function getParent()
    {
        return DynamicParametersType::class;
    }
}
