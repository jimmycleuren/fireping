<?php

declare(strict_types=1);

namespace App\Entity\AlertDestination;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="alert_destination_webhook")
 * @UniqueEntity("name", entityClass="App\Entity\AlertDestination\AlertDestination")
 */

class Webhook extends AlertDestination
{
    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    private $url;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}