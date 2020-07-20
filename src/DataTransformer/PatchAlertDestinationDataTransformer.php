<?php
declare(strict_types=1);

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\PatchAlertDestination;
use App\Entity\AlertDestination;
use App\Factory\AlertDestinationParameterFactory;
use App\Model\Parameter\DynamicParametersInterface;
use App\Model\Parameter\NullParameters;

class PatchAlertDestinationDataTransformer implements DataTransformerInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        /** @var AlertDestination $destination */
        $destination = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];

        $parameters = new NullParameters();
        if (\is_array($object->parameters)) {
            $parameters = $this->createParameters($destination->getType(), $object->parameters);
        }

        $this->validator->validate($parameters);

        if ($object->name !== null) {
            $destination->setName($object->name);
        }

        $destination->setParameters($parameters);

        return $destination;
    }

    private function createParameters(string $type, array $parameters): DynamicParametersInterface
    {
        return (new AlertDestinationParameterFactory())->make($type, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof PatchAlertDestination || !isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            return false;
        }

        return AlertDestination::class === $to && null !== ($context['input']['class'] ?? null);
    }
}