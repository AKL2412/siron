<?php 
namespace  TRC\CoreBundle\Connected;
use TRC\CoreBundle\Entity\Log;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Session;

class Pagination{

	protected $em;
	protected $repo;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	}

}