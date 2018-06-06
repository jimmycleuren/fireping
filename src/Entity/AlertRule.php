<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Alert
 *
 * @ORM\Table(name="alert_rule")
 * @ORM\Entity(repositoryClass="App\Repository\AlertRuleRepository")
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
     * @var Probe
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
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $messageUp;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $messageDown;

    /**
     * Each rule can optionally have a parent rule.
     *
     * @ORM\ManyToOne(targetEntity="AlertRule", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AlertRule", mappedBy="parent")
     */
    private $children;

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
     * @param \App\Entity\Probe $probe
     *
     * @return AlertRule
     */
    public function setProbe(\App\Entity\Probe $probe)
    {
        $this->probe = $probe;

        return $this;
    }

    /**
     * Get probe
     *
     * @return \App\Entity\Probe
     */
    public function getProbe()
    {
        return $this->probe;
    }

    /**
     * Set parent
     *
     * @param \App\Entity\AlertRule $parent
     *
     * @return AlertRule
     */
    public function setParent(\App\Entity\AlertRule $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \App\Entity\AlertRule
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Set messageUp
     *
     * @param string $messageUp
     *
     * @return AlertRule
     */
    public function setMessageUp($messageUp)
    {
        $this->messageUp = $messageUp;

        return $this;
    }

    /**
     * Get messageUp
     *
     * @return string
     */
    public function getMessageUp()
    {
        return $this->messageUp;
    }

    /**
     * Set messageDown
     *
     * @param string $messageUp
     *
     * @return AlertRule
     */
    public function setMessageDown($messageDown)
    {
        $this->messageDown = $messageDown;

        return $this;
    }

    /**
     * Get messageDown
     *
     * @return string
     */
    public function getMessageDown()
    {
        return $this->messageDown;
    }

    public function __toString()
    {
        return $this->name;
    }
}
