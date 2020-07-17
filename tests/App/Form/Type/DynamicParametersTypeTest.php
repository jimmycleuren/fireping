<?php

declare(strict_types=1);

namespace App\Tests\App\Form\Type;

use App\Form\Type\DynamicParametersType;
use PHPUnit\Framework\TestCase;

class DynamicParametersTypeTest extends TestCase
{
    public function testBlockPrefix(): void
    {
        self::assertSame('dynamic_parameters', (new DynamicParametersType())->getBlockPrefix());
    }
}