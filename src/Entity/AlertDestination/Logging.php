<?php

declare(strict_types=1);

namespace App\Entity\AlertDestination;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="alert_destination_log")
 */
class Logging extends AlertDestination
{

}