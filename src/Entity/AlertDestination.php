<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\Parameters\AlertDestination\HttpParameters;
use App\Model\Parameters\AlertDestination\MailParameters;
use App\Model\Parameters\AlertDestination\MonologParameters;
use App\Model\Parameters\AlertDestination\SlackParameters;
use App\Model\Parameters\JsonParametersInterface;
use App\Model\Parameters\NullParameters;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AlertDestination.
 *
 * @ORM\Table(name="alert_destination")
 * @ORM\Entity(repositoryClass="App\Repository\AlertDestinationRepository")
 * @ApiResource
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

    public function getParameters(): JsonParametersInterface
    {
        $parameters = $this->parameters ?? [];

        switch ($this->type) {
            case 'http': return HttpParameters::fromArray($parameters);
            case 'monolog': return MonologParameters::fromArray($parameters);
            case 'slack': return SlackParameters::fromArray($parameters);
            case 'mail': return MailParameters::fromArray($parameters);
            default: return NullParameters::fromArray([]);
        }
    }

    public function setParameters(JsonParametersInterface $parameters): void
    {
        $this->parameters = $parameters->asArray();
    }

    public function __toString(): ?string
    {
        return $this->name;
    }
}
