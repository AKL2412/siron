<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Entity\DDC\Fichier;
use TRC\CoreBundle\Entity\Client\Client;
use TRC\CoreBundle\Systemes\General\Core;

class WebController extends Controller
{

	
    public function webServiceAction(Request $request)
    {

    	
    	try {

    		$racine = $request->request->get('racine');
    		$client = new \nusoap_client("http://localhost/center/center.php");
    		$client->soap_defencoding = 'UTF-8';
    		$result = $client->call("creditClient", array("racine" => $racine));
    		return $this->render('TRCGSCBundle:WEB:index.html.twig',
        	array('result'=>$result,'racine'=>$racine));
    	} catch (\Exception $e) {
    		return new Response($e->getMessage());
    	}

    	//*/
        
    }

}