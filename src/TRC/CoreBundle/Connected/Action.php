<?php 
namespace  TRC\CoreBundle\Connected;
use TRC\CoreBundle\Entity\Log;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
class Action extends Controller{

	protected $em;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	}

	public function onKernelRequest(GetResponseEvent $event)
    {
        $kernel    = $event->getKernel();
        $request   = $event->getRequest();

        //*/
     
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response  = $event->getResponse();
        $request   = $event->getRequest();
        $kernel    = $event->getKernel();

        $routes = $request->attributes->all();
        $blackListes = array('fos_user_security_login','trc_core_logout','fos_user_security_check','fos_user_security_logout','trc_core_homepage');
        if(array_key_exists("_route", $routes)){
            $url = $routes['_route'];
            if(!in_array($url, $blackListes)){
                $session = new Session();
                 $compte = $session->get('compte');
                 $message = "Désolé! Votre compte n'est pas parfaitement parametré. Vous ne pouvez continuer. Veuillez contacter l'administrateur pour ce petit soucis. Merci";
                //$message = $this->generateUrl('trc_core_societe');
                //die($message);
                //if(!isset($compte) || is_null($compte))
                    // $event->setResponse(new Response($message));
            }
        }
       
       
       
        
        
            
         

        
    }
}