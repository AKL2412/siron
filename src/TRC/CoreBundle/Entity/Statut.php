<?php

namespace TRC\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
/**
 * Statut
 *
 * @ORM\Table(name="statut")
 * @ORM\Entity(repositoryClass="TRC\CoreBundle\Repository\StatutRepository")
 */
class Statut
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
     * @ORM\Column(name="at", type="datetime")
     */
    private $at;
    
    /**
     * @var string
     *
     * @ORM\Column(name="commentaire", type="text", nullable=true)
     */
    private $commentaire;
    
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Alerte")
     * @ORM\JoinColumn(nullable=true)
     */
    private $alerte;
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\StatutAlerte")
     * @ORM\JoinColumn(nullable=true)
     */
    private $statut;
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Agent")
     * @ORM\JoinColumn(nullable=false)
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

    public function __construct(\TRC\CoreBundle\Entity\Agent $agent,\TRC\CoreBundle\Entity\Alerte $alerte){
        $this->at = new \DateTime();
        $this->alerte = $alerte;
        $this->agent = $agent;
    }
    /**
     * Set at
     *
     * @param \DateTime $at
     *
     * @return Statut
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
     * Set commentaire
     *
     * @param string $commentaire
     *
     * @return Statut
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set alerte
     *
     * @param \TRC\CoreBundle\Entity\Alerte $alerte
     *
     * @return Statut
     */
    public function setAlerte(\TRC\CoreBundle\Entity\Alerte $alerte = null)
    {
        $this->alerte = $alerte;

        return $this;
    }

    /**
     * Get alerte
     *
     * @return \TRC\CoreBundle\Entity\Alerte
     */
    public function getAlerte()
    {
        return $this->alerte;
    }

    /**
     * Set statut
     *
     * @param \TRC\CoreBundle\Entity\StatutAlerte $statut
     *
     * @return Statut
     */
    public function setStatut(\TRC\CoreBundle\Entity\StatutAlerte $statut = null)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return \TRC\CoreBundle\Entity\StatutAlerte
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set agent
     *
     * @param \TRC\CoreBundle\Entity\Agent $agent
     *
     * @return Statut
     */
    public function setAgent(\TRC\CoreBundle\Entity\Agent $agent)
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
