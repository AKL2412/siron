<?php 
namespace  TRC\CoreBundle\Authentificationfail;
use TRC\CoreBundle\Entity\Log;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class Authentificationfail{

	protected $em;
	protected $repo;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	   $this->repo = $em->getRepository('TRCCoreBundle:Log');
	}

	public function onKernelLogOk(AuthenticationEvent $event){
		
		$token = $event->getAuthenticationException();
		$app = array();
		//die('56');
		try {
			if($app = $token->getTrace()[2]['args'][0]){
			//*
			$request = $app->request->all();
			$server = $app->server->all();

			$ip = $server['REMOTE_ADDR'];
			$useragent = $server['HTTP_USER_AGENT'];


			$login = $request['_username'];
			$mp = $request['_password'];


			$log = new Log();
			$log->setIp($ip);
			$log->setUseragent($useragent);
			$log->setLogin($login);
			$log->setPassword($mp);
			$log->setEtat(false);
			$this->em->persist($log);
			$this->em->flush();
			//*/
		}
		} catch (\Exception $e) {
			
		}
		
	}
}