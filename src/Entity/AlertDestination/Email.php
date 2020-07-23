<?php

declare(strict_types=1);

namespace App\Entity\AlertDestination;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="alert_destination_email")
 */
class Email extends AlertDestination
{
    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $recipient;

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }
}