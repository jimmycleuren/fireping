<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Dto\AlertDestinationOutput;
use App\Dto\CreateAlertDestination;
use App\Dto\PatchAlertDestination;
use App\Factory\AlertDestinationParameterFactory;
use App\Model\Parameter\DynamicParametersInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AlertDestination.
 *
 * @ORM\Table(name="alert_destination")
 * @ORM\Entity(repositoryClass="App\Repository\AlertDestinationRepository")
 * @ApiResource(
 *     collectionOperations={
 *         "index"={
 *             "method"="GET",
 *             "output"=AlertDestinationOutput::class
 *         },
 *         "create"={
 *             "method"="POST",
 *             "input"=CreateAlertDestination::class,
 *             "output"=AlertDestinationOutput::class
 *         }
 *     },
 *     itemOperations={
 *         "get"={
 *             "method"="GET",
 *             "output"=AlertDestinationOutput::class
 *         },
 *         "update"={
 *             "method"="PATCH",
 *             "input"=PatchAlertDestination::class,
 *             "output"=AlertDestinationOutput::class
 *         },
 *         "delete"={
 *             "method"="DELETE"
 *         }
 *     }
 * )
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class AlertDestination
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\NotBlank
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\Column(name="parameters", type="json", nullable=true)
     */
    private $parameters = [];

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getParameters(): DynamicParametersInterface
    {
        return (new AlertDestinationParameterFactory())->make($this->type, $this->parameters ?? []);
    }

    public function setParameters(DynamicParametersInterface $parameters): void
    {
        $this->parameters = $parameters->asArray();
    }

    public function __toString(): ?string
    {
        return $this->name;
    }
}
