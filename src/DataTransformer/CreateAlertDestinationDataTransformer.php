<?php
declare(strict_types=1);

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\CreateAlertDestination;
use App\Entity\AlertDestination;
use App\Factory\AlertDestinationParameterFactory;
use App\Model\Parameter\DynamicParametersInterface;
use App\Model\Parameter\NullParameters;

class CreateAlertDestinationDataTransformer implements DataTransformerInterface
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
        $parameters = new NullParameters();
        if (\is_array($object->parameters)) {
            $parameters = $this->createParameters($object->type, $object->parameters);
        }
        $object->parameters = $parameters;

        $this->validator->validate($object);

        $existingAlertDestination = new AlertDestination();
        $existingAlertDestination->setName($object->name);
        $existingAlertDestination->setType($object->type);
        $existingAlertDestination->setParameters($parameters);

        return $existingAlertDestination;
    }

    /**
     * @inheritDoc
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof CreateAlertDestination || isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            return false;
        }

        return AlertDestination::class === $to && null !== ($context['input']['class'] ?? null);
    }

    private function createParameters(string $type, array $parameters): DynamicParametersInterface
    {
        return (new AlertDestinationParameterFactory())->make($type, $parameters);
    }
}