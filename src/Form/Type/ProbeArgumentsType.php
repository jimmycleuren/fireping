<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;

class ProbeArgumentsType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'probe_arguments';
    }
}
