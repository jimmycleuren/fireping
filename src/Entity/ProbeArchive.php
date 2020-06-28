<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProbeArchive.
 *
 * @ORM\Table(name="probe_archive")
 * @ORM\Entity(repositoryClass="App\Repository\ProbeArchiveRepository")
 * @ApiResource
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ProbeArchive
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
     * @var Probe
     *
     * @ORM\ManyToOne(targetEntity="Probe", inversedBy="archives")
     * @ORM\JoinColumn(name="probe_id", referencedColumnName="id")
     * @Assert\NotBlank
     */
    private $probe;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $function;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     */
    private $steps;

    /**
     * @var int
     *
     * @ORM\Column(name="archive_rows", type="integer")
     * @Assert\NotBlank
     */
    private $rows;

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
    public function getFunction(): ?string
    {
        return $this->function;
    }

    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * @return int
     */
    public function getSteps(): ?int
    {
        return $this->steps;
    }

    public function setSteps(int $steps): void
    {
        $this->steps = $steps;
    }

    /**
     * @return int
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function setRows(int $rows): void
    {
        $this->rows = $rows;
    }

    /**
     * @return Probe
     */
    public function getProbe(): ?Probe
    {
        return $this->probe;
    }

    public function setProbe(Probe $probe): void
    {
        $this->probe = $probe;
    }

    public function __toString()
    {
        return $this->function.'-'.$this->steps.'-'.$this->rows;
    }
}
