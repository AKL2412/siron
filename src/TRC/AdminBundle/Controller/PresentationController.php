<?php

namespace TRC\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Systemes\General\Core;
class PresentationController extends Controller{

	public function trackingAction($pagination){

		return $this->render('TRCAdminBundle:Presentation:tracking.html.twig',
        	array(
        		'pagination'=> $pagination
        		));
	}

	public function logAction($pagination){

		return $this->render('TRCAdminBundle:Presentation:log.html.twig',
        	array(
        		'pagination'=> $pagination
        		));
	}
}