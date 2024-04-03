<?php
declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TracerouteArgumentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Construct your arguments form here.
    }

    public function getParent(): ?string
    {
        return ProbeArgumentsType::class;
    }
}
