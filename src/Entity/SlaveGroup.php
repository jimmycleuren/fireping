<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SlaveGroup.
 *
 * @ORM\Table(name="slave_group")
 * @ORM\Entity(repositoryClass="App\Repository\SlaveGroupRepository")
 * @ApiResource(attributes={"normalization_context"={"groups"={"slavegroup"}}})
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class SlaveGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"slavegroup"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Groups({"slavegroup"})
     */
    private $name;

    /**
     * @var ArrayCollection<int, Device>
     * @ORM\ManyToMany(targetEntity="Device", mappedBy="slavegroups")
     */
    private $devices;

    /**
     * @var ArrayCollection<int, Domain>
     * @ORM\ManyToMany(targetEntity="Domain", mappedBy="slavegroups")
     */
    private $domains;

    /**
     * @var ArrayCollection<int, Slave>
     * @ORM\OneToMany(targetEntity="Slave", mappedBy="slavegroup")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @ORM\OrderBy({"id" = "asc"})
     */
    private $slaves;

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return SlaveGroup
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return SlaveGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add device.
     *
     * @return SlaveGroup
     */
    public function addDevice(\App\Entity\Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove device.
     */
    public function removeDevice(\App\Entity\Device $device): void
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add domain.
     *
     * @return SlaveGroup
     */
    public function addDomain(\App\Entity\Domain $domain)
    {
        $this->domains[] = $domain;

        return $this;
    }

    /**
     * Remove domain.
     */
    public function removeDomain(\App\Entity\Domain $domain): void
    {
        $this->domains->removeElement($domain);
    }

    /**
     * Get domains.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add slave.
     *
     * @return SlaveGroup
     */
    public function addSlave(\App\Entity\Slave $slave)
    {
        $this->slaves[] = $slave;

        return $this;
    }

    /**
     * Remove slave.
     */
    public function removeSlave(\App\Entity\Slave $slave): void
    {
        $this->slaves->removeElement($slave);
    }

    /**
     * Get slaves.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSlaves()
    {
        return $this->slaves;
    }
}
