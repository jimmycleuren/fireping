<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Alert
 *
 * @ORM\Table(name="alert_rule")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AlertRuleRepository")
 * @ApiResource
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class AlertRule
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
     * @var probe
     *
     * @ORM\ManyToOne(targetEntity="Probe")
     * @ORM\JoinColumn(name="probe_id", referencedColumnName="id")
     * @Assert\NotBlank
     */
    private $probe;

    /**
     * @var string
     *
     * @ORM\Column(name="datasource", type="string", length=255)
     * @Assert\NotBlank
     */
    private $datasource;

    /**
     * @var string
     *
     * @ORM\Column(name="pattern", type="string", length=255)
     * @Assert\NotBlank
     */
    private $pattern;


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
     * @return AlertRule
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
     * Set datasource
     *
     * @param string $datasource
     *
     * @return AlertRule
     */
    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;

        return $this;
    }

    /**
     * Get datasource
     *
     * @return string
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * Set pattern
     *
     * @param string $pattern
     *
     * @return AlertRule
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set probe
     *
     * @param \AppBundle\Entity\Probe $probe
     *
     * @return AlertRule
     */
    public function setProbe(\AppBundle\Entity\Probe $probe = null)
    {
        $this->probe = $probe;

        return $this;
    }

    /**
     * Get probe
     *
     * @return \AppBundle\Entity\Probe
     */
    public function getProbe()
    {
        return $this->probe;
    }
}
