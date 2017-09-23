<?php

namespace TRC\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Alerte
 *
 * @ORM\Table(name="alerte")
 * @ORM\Entity(repositoryClass="TRC\CoreBundle\Repository\AlerteRepository")
 */
class Alerte
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
     * @var \DateTime
     *
     * @ORM\Column(name="dateOperation", type="date")
     */
    private $dateOperation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="at", type="datetime")
     */
    private $at;

    /**
     * @var float
     *
     * @ORM\Column(name="montant", type="float")
     */
    private $montant;
    
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Client")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;
    
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\TypeOperation")
     * @ORM\JoinColumn(nullable=true)
     */
    private $operation;
    
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Agent")
     * @ORM\JoinColumn(nullable=true)
     */
    private $agent;


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
     * Set dateOperation
     *
     * @param \DateTime $dateOperation
     *
     * @return Alerte
     */
    public function setDateOperation($dateOperation)
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    /**
     * Get dateOperation
     *
     * @return \DateTime
     */
    public function getDateOperation()
    {
        return $this->dateOperation;
    }

    /**
     * Set at
     *
     * @param \DateTime $at
     *
     * @return Alerte
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * Get at
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * Set montant
     *
     * @param float $montant
     *
     * @return Alerte
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return float
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set client
     *
     * @param \TRC\CoreBundle\Entity\Client $client
     *
     * @return Alerte
     */
    public function setClient(\TRC\CoreBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \TRC\CoreBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set operation
     *
     * @param \TRC\CoreBundle\Entity\TypeOperation $operation
     *
     * @return Alerte
     */
    public function setOperation(\TRC\CoreBundle\Entity\TypeOperation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \TRC\CoreBundle\Entity\TypeOperation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set agent
     *
     * @param \TRC\CoreBundle\Entity\Agent $agent
     *
     * @return Alerte
     */
    public function setAgent(\TRC\CoreBundle\Entity\Agent $agent = null)
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent
     *
     * @return \TRC\CoreBundle\Entity\Agent
     */
    public function getAgent()
    {
        return $this->agent;
    }
}
