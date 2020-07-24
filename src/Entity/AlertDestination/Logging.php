<?php

declare(strict_types=1);

namespace App\Entity\AlertDestination;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ApiResource()
 * @ORM\Table(name="alert_destination_log")
 * @UniqueEntity("name", entityClass="App\Entity\AlertDestination\AlertDestination")
 */
class Logging extends AlertDestination
{

}