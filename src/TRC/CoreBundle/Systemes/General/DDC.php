<?php 

namespace  TRC\CoreBundle\Systemes\General;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Entity\Journal as JournalEntity;
use Symfony\Component\HttpFoundation\File\File;
use TRC\UserBundle\Entity\User as Compte;
use TRC\CoreBundle\Entity\Acteur as Actor;

use TRC\CoreBundle\Entity\DDC\PDDC;
use TRC\CoreBundle\Entity\DDC\EDDC;
use TRC\CoreBundle\Entity\DDC\DOCDDC;
use TRC\CoreBundle\Entity\DDC\GDDC;

use TRC\CoreBundle\Systemes\General\GU;

class DDC
{

    protected $em;
	protected $gu;
	protected $cheminPrincipal;

    public function __construct(\TRC\CoreBundle\Systemes\General\GU $gu){
       
       $this->gu = $gu;

    }

    
	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	}

	
    public function estMonClient(\TRC\CoreBundle\Entity\GSC\Gestionnaire $gestionnaire = null, \TRC\CoreBundle\Entity\GSC\Client $client){


        if ($gestionnaire == $client->getGestionnaire()) 
            return true;
        return false;
    }

    

}