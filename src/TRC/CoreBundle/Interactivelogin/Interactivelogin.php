<?php 
namespace  TRC\CoreBundle\Interactivelogin;
use TRC\CoreBundle\Entity\Log;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class Interactivelogin{

	protected $em;
	protected $repo;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	   $this->repo = $em->getRepository('TRCCoreBundle:Log');
	}

	public function onKernelLogOk(InteractiveLoginEvent $event){

		$ip = $_SERVER['REMOTE_ADDR'];
		$token = $event->getAuthenticationToken();
		$request = $event->getRequest();

		echo "<pre>";

		print_r($token);

		echo "</pre>";

		die('Interactivelogin');
		//$user = $token->getUser();

		/*
		$session = new Session();
		if(gettype($user) != 'string'){
			
			


			$log = new Log();
			$log->setUser($user);
			$log->setIp($ip);
			$this->em->persist($log);


			$user->setLogged(true);
			$user->setTimeUpdate(new \DateTime());
			$user->setLogIn($log);


			$this->em->flush();

			//*/
		//}
	}
}