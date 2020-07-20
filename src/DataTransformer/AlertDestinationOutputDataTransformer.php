<?php
declare(strict_types=1);

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\AlertDestinationOutput;
use App\Entity\AlertDestination;

class AlertDestinationOutputDataTransformer implements DataTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform($object, string $to, array $context = [])
    {
        $out = new AlertDestinationOutput();

        $out->id = $object->getId();
        $out->name = $object->getName();
        $out->type = $object->getType();
        $out->parameters = $object->getParameters()->asArray();

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return AlertDestinationOutput::class === $to && $data instanceof AlertDestination;
    }
}