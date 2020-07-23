<?php

namespace App\Entity\AlertDestination;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="alert_destination")
 * @ORM\Entity(repositoryClass="App\Repository\AlertDestinationRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type_discriminator", type="string")
 * @ORM\DiscriminatorMap({"slack" = "Slack", "webhook" = "Webhook", "email" = "Email", "monolog" = "Logging"})
 * @ApiResource
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
abstract class AlertDestination
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
    private $parameters;

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

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters): void
    {
        $this->parameters = $parameters;
    }

    public function __toString(): ?string
    {
        return $this->name;
    }
}
