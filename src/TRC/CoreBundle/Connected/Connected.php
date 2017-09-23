<?php 
namespace  TRC\CoreBundle\Connected;
use TRC\CoreBundle\Entity\Log;
use TRC\CoreBundle\Entity\Enligne;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Session;

class Connected{

	protected $em;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	}

	public function onKernelLogOk(AuthenticationEvent $event){

		$ip = $_SERVER['REMOTE_ADDR'];
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$token = $event->getAuthenticationToken();
		$user = $token->getUser();
		
		$session = new Session();
		$session->set('compte',null);
		$session->set('poste',"inconnu");
		if(gettype($user) != 'string'){

			$utilisateur = $this->em->getRepository('TRCCoreBundle:Utilisateur')
							->findOneByCompte($user);
			//*
			if(is_null($utilisateur))
				throw new \Exception("Désolé! Votre compte n'est pas parfaitement parametré. Vous ne pouvez continuer. Veuillez contacter l'administrateur pour ce petit soucis. Merci", 1);
			$session->set('compte',$utilisateur);
			//*/
			$poste = $this->em->getRepository('TRCCoreBundle:Poste')
							->findOneByEmploye($utilisateur);

			if(!is_null($poste))
				$session->set('poste',$poste->getFonction()->getNom());
			$logs = $this->em->getRepository('TRCCoreBundle:Log')
					->findBy(
						array(
							'user'=>$user,
							'logOutAt'=>null
							),
						array(),null,0);
			foreach ($logs as $key => $value) {
				$value->setLogOutAt(new \DateTime());
				$value->setDetails("Déconnexion démat.");
			}
			$log = new Log();
			$log->setUser($user);
			$log->setIp($ip);
			$log->setEtat(true);
			$log->setUseragent($useragent);
			

			
			$this->em->persist($log);
			$this->em->flush();
			$session->set('log',$log->getId());
			
		}
		
	}
}