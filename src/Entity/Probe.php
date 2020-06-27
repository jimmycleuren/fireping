<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\HttpArguments;
use App\NullArguments;
use App\PingArguments;
use App\ProbeArgumentsInterface;
use App\TracerouteArguments;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Probe
 *
 * @ORM\Table(name="probe")
 * @ORM\Entity(repositoryClass="App\Repository\ProbeRepository")
 * @ApiResource
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Probe
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
    private $type = 'ping';

    /**
     * @var int
     *
     * @ORM\Column(name="step", type="integer")
     * @Assert\NotBlank
     */
    private $step;

    /**
     * @var int
     *
     * @ORM\Column(name="samples", type="integer")
     * @Assert\NotBlank
     */
    private $samples;

    /**
     * @var string
     *
     * @ORM\Column(name="arguments", type="text")
     */
    private $arguments = '{}';

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="ProbeArchive", mappedBy="probe", fetch="EXTRA_LAZY")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     */
    private $archives;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->archives = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param int $id
     *
     * @return Probe
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Probe
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Probe
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set step
     *
     * @param integer $step
     *
     * @return Probe
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get step
     *
     * @return int
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set samples
     *
     * @param integer $samples
     *
     * @return Probe
     */
    public function setSamples($samples)
    {
        $this->samples = $samples;

        return $this;
    }

    /**
     * Get samples
     *
     * @return int
     */
    public function getSamples()
    {
        return $this->samples;
    }

    /**
     * @param ProbeArgumentsInterface $arguments
     *
     * @return Probe
     */
    public function setArguments(ProbeArgumentsInterface $arguments): Probe
    {
        $this->arguments = json_encode($arguments->asArray(), JSON_THROW_ON_ERROR, 512);

        return $this;
    }

    /**
     * @return ProbeArgumentsInterface
     */
    public function getArguments(): ProbeArgumentsInterface
    {
        switch ($this->type) {
            case 'ping': return PingArguments::fromJsonString($this->arguments ?? '{}');
            case 'traceroute': return TracerouteArguments::fromJsonString($this->arguments ?? '{}');
            case 'http': return HttpArguments::fromJsonString($this->arguments ?? '{}');
            default: return new NullArguments();
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getArchives()
    {
        return $this->archives;
    }

    /**
     * Add ProbeArchive
     *
     * @param ProbeArchive $archive
     *
     * @return Probe
     */
    public function addArchive(ProbeArchive $archive)
    {
        $this->archives[] = $archive;

        return $this;
    }

    /**
     * Remove ProbeArchive
     *
     * @param ProbeArchive $archive
     */
    public function removeArchive(ProbeArchive $archive)
    {
        $this->archives->removeElement($archive);
    }

    public function __toString()
    {
        return $this->name;
    }
}
