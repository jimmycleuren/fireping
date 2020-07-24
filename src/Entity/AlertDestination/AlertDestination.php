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
 * @ORM\DiscriminatorMap({
 *     "slack" = "SlackDestination",
 *     "webhook" = "WebhookDestination",
 *     "email" = "EmailDestination",
 *     "monolog" = "LogDestination"
 * })
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
    protected $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank
     */
    protected $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
