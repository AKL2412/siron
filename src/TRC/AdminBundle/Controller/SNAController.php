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

use TRC\CoreBundle\Form\SNA\TypeDossierType;
use TRC\CoreBundle\Entity\SNA\TypeDossier;

use TRC\CoreBundle\Form\SNA\TypeGarantieType;
use TRC\CoreBundle\Entity\SNA\TypeGarantie;
class SNAController extends Controller{

	public function statutDossierAction(Request $request){

    	$em = $this->get('doctrine')->getManager();
    	$statut = new TypeDossier();

        
    	$form = $this->get('form.factory')->create(new TypeDossierType(),$statut);

    	if($form->handleRequest($request)->isValid()){

           
    		  $em->persist($statut);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'app'=>"SNA",
                        'action'=>"Ajout de statut",
                        'description'=>"Ajouter de statut : <b><u>".$statut->getNom()."</u></b> "
                        ));
            

    		return $this->redirect($this->generateUrl('trc_admin_statut_dossier'));
    	}
    	//Les parametres
    	$dql   = "SELECT p FROM TRCCoreBundle:SNA\TypeDossier p ";
        $query = $em->createQuery($dql);
        //$query->setParameter('conteneur',$instance->getParametre());
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
    	return $this->render('TRCAdminBundle:SNA:statutDossier.html.twig',
        	array(
        		'pagination'=>$pagination,
        		'form'=>$form->createView()
        		));
    }

    public function garantieDossierAction(Request $request){

        $em = $this->get('doctrine')->getManager();
        $statut = new TypeGarantie();

        
        $form = $this->get('form.factory')->create(new TypeGarantieType(),$statut);

        if($form->handleRequest($request)->isValid()){

           
              $em->persist($statut);
                 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'app'=>"SNA",
                        'action'=>"Ajout de garantie",
                        'description'=>"Ajouter de garantie : <b><u>".$statut->getNom()."</u></b> "
                        ));
            

            return $this->redirect($this->generateUrl('trc_admin_garantie_dossier'));
        }
        //Les parametres
        $dql   = "SELECT p FROM TRCCoreBundle:SNA\TypeGarantie p ";
        $query = $em->createQuery($dql);
        //$query->setParameter('conteneur',$instance->getParametre());
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
        return $this->render('TRCAdminBundle:SNA:garantieDossier.html.twig',
            array(
                'pagination'=>$pagination,
                'form'=>$form->createView()
                ));
    }
}