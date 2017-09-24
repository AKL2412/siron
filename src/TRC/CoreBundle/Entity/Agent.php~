<?php

namespace TRC\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Agent
 *
 * @ORM\Table(name="agent")
 * @ORM\Entity(repositoryClass="TRC\CoreBundle\Repository\AgentRepository")
 */
class Agent
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
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=255)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;
    
    /*-----------------------------------------------------------
     *                          RELATIONS
     ---------------------------------------------------------*/
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Entite")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entite;
    
    /**
     * @ORM\ManyToOne(targetEntity="TRC\CoreBundle\Entity\Profil")
     * @ORM\JoinColumn(nullable=true)
     */
    private $profil;
    
    /**
     * @ORM\OneToOne(targetEntity="TRC\UserBundle\Entity\User",cascade={"remove", "persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $compte;

    
    public function nomprenom(){
        return $this->prenom." ".strtoupper($this->nom);
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
     * Set nom
     *
     * @param string $nom
     *
     * @return Agent
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Agent
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Agent
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set entite
     *
     * @param \TRC\CoreBundle\Entity\Entite $entite
     *
     * @return Agent
     */
    public function setEntite(\TRC\CoreBundle\Entity\Entite $entite = null)
    {
        $this->entite = $entite;

        return $this;
    }

    /**
     * Get entite
     *
     * @return \TRC\CoreBundle\Entity\Entite
     */
    public function getEntite()
    {
        return $this->entite;
    }

    /**
     * Set profil
     *
     * @param \TRC\CoreBundle\Entity\Profil $profil
     *
     * @return Agent
     */
    public function setProfil(\TRC\CoreBundle\Entity\Profil $profil = null)
    {
        $this->profil = $profil;

        return $this;
    }

    /**
     * Get profil
     *
     * @return \TRC\CoreBundle\Entity\Profil
     */
    public function getProfil()
    {
        return $this->profil;
    }

    /**
     * Set compte
     *
     * @param \TRC\UserBundle\Entity\User $compte
     *
     * @return Agent
     */
    public function setCompte(\TRC\UserBundle\Entity\User $compte = null)
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Get compte
     *
     * @return \TRC\UserBundle\Entity\User
     */
    public function getCompte()
    {
        return $this->compte;
    }
}
