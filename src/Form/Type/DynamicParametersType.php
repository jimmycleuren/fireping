<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;

class DynamicParametersType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'dynamic_parameters';
    }
}
