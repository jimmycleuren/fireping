<?php

declare(strict_types=1);

namespace App\Admin\Field;

use App\Form\Type\PingArgumentsType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

class JsonParametersField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/json_parameters.html.twig')
            ->setRequired(false)
        ;
    }
}