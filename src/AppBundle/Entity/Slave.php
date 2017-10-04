<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Slave
 *
 * @ORM\Table(name="slave")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SlaveRepository")
 * @ApiResource(attributes={"normalization_context"={"groups"={"slave"}}})
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Slave
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=255)
     * @ORM\Id
     * @Groups({"slave"})
     */
    private $id;

    /**
     * @var slavegroup
     *
     * @ORM\ManyToOne(targetEntity="SlaveGroup")
     * @ORM\JoinColumn(name="slavegroup_id", referencedColumnName="id")
     * @Groups({"slave"})
     */
    private $slavegroup;

    /**
     * @var datetime
     *
     * @ORM\Column(name="last_contact", type="datetime")
     */
    private $lastContact;

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Slave
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
     * Set lastContact
     *
     * @param string $lastContact
     *
     * @return Slave
     */
    public function setLastContact($lastContact)
    {
        $this->lastContact = $lastContact;

        return $this;
    }

    /**
     * Get lastcontact
     *
     * @return string
     */
    public function getLastContact()
    {
        return $this->lastContact;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add device
     *
     * @param \AppBundle\Entity\Device $device
     *
     * @return Slave
     */
    public function addDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove device
     *
     * @param \AppBundle\Entity\Device $device
     */
    public function removeDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add domain
     *
     * @param \AppBundle\Entity\Domain $domain
     *
     * @return Slave
     */
    public function addDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains[] = $domain;

        return $this;
    }

    /**
     * Remove domain
     *
     * @param \AppBundle\Entity\Domain $domain
     */
    public function removeDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains->removeElement($domain);
    }

    /**
     * Get domains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * Set slavegroup
     *
     * @param \AppBundle\Entity\SlaveGroup $slavegroup
     *
     * @return Slave
     * @Groups({"slave"})
     */
    public function setSlaveGroup(\AppBundle\Entity\SlaveGroup $slavegroup = null)
    {
        $this->slavegroup = $slavegroup;

        return $this;
    }

    /**
     * Get slavegroup
     *
     * @return \AppBundle\Entity\SlaveGroup
     * @Groups({"slave"})
     */
    public function getSlaveGroup()
    {
        return $this->slavegroup;
    }
}
