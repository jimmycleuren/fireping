<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;

class JsonParametersType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'json_parameters';
    }
}
