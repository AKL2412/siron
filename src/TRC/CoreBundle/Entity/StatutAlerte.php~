<?php

namespace TRC\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatutAlerte
 *
 * @ORM\Table(name="statut_alerte")
 * @ORM\Entity(repositoryClass="TRC\CoreBundle\Repository\StatutAlerteRepository")
 */
class StatutAlerte
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
     * @ORM\Column(name="code", type="string", length=5, unique=true)
     */
    private $code;
    /**
     * @var bool
     *
     * @ORM\Column(name="defaut", type="boolean")
     */
    private $defaut;

    /**
     * @var bool
     *
     * @ORM\Column(name="cloture", type="boolean")
     */
    private $cloture;

    public function __construct($defaut = false){
        $this->defaut = $defaut;
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
     * @return StatutAlerte
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
     * Set code
     *
     * @param string $code
     *
     * @return StatutAlerte
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set defaut
     *
     * @param boolean $defaut
     *
     * @return StatutAlerte
     */
    public function setDefaut($defaut)
    {
        $this->defaut = $defaut;

        return $this;
    }

    /**
     * Get defaut
     *
     * @return boolean
     */
    public function getDefaut()
    {
        return $this->defaut;
    }

    /**
     * Set cloture
     *
     * @param boolean $cloture
     *
     * @return StatutAlerte
     */
    public function setCloture($cloture)
    {
        $this->cloture = $cloture;

        return $this;
    }

    /**
     * Get cloture
     *
     * @return boolean
     */
    public function getCloture()
    {
        return $this->cloture;
    }
}
