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
use TRC\CoreBundle\Entity\Societe;
use TRC\CoreBundle\Form\SocieteType;
use TRC\CoreBundle\Entity\Utilisateur;
use TRC\CoreBundle\Entity\Poste;
use TRC\CoreBundle\Form\UtilisateurType;
use TRC\CoreBundle\Form\PosteType;
use Symfony\Component\HttpFoundation\File\File;


use TRC\CoreBundle\Entity\Service;
use TRC\CoreBundle\Form\ServiceType;
use TRC\CoreBundle\Entity\Fonction;
use TRC\CoreBundle\Form\FonctionType;

use TRC\CoreBundle\Entity\Objet\Objet;
use TRC\CoreBundle\Form\Objet\ObjetType;

use TRC\CoreBundle\Entity\Core\Decision;
use TRC\CoreBundle\Form\Core\DecisionType;

use TRC\CoreBundle\Entity\Objet\Parametre;
use TRC\CoreBundle\Entity\Objet\ValeurParametre;
use TRC\CoreBundle\Form\Objet\ParametreType;

use TRC\CoreBundle\Entity\Objet\Instance;
use TRC\CoreBundle\Entity\Objet\Documentation;
use TRC\CoreBundle\Entity\Objet\Document;
use TRC\CoreBundle\Form\Objet\InstanceType;
use TRC\CoreBundle\Form\Objet\DocumentType;
class ObjetController extends Controller{

	public function definitionAction(Request $request,$code = null)
    {
    	$em = $this->get('doctrine')->getManager();
    	$objet = null;
    	if(!is_null($code)){
    		$objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
    					->findOneByCode($code);
    		if(is_null($objet))
    			throw new \Exception("Erreur de code : ".$code, 1);
    			
    	}
    	if(is_null($objet))
    		$objet = new Objet();
    	$form = $this->get('form.factory')->create(new ObjetType(),$objet);

    	if($form->handleRequest($request)->isValid()){

    		if(is_null($code)){

    			$em->persist($objet);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout d'objet",
                        'description'=>"Ajouter l'objet: <b><u>".$objet->getNom()."</u></b> "
                        ));
                 $this->get('trc_core.gu')->creerDossierObjet($objet);
    		}
    		$em->flush();

    		return $this->redirect($this->generateUrl('trc_admin_objets_voir',array('code'=>$objet->getCode())));
    	}
    	$dql   = "SELECT a FROM TRCCoreBundle:Objet\Objet a WHERE a.parent is null ";
        $query = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
        return $this->render('TRCAdminBundle:Objet:definition.html.twig',
        	array('form'=>$form->createView(),'pagination'=>$pagination));
    }

    public function voirObjetAction(Request $request,$code){

    	$em = $this->get('doctrine')->getManager();
    	$objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
    					->findOneByCode($code);
    		if(is_null($objet))
    			throw new \Exception("Erreur de code : ".$code, 1);

    	$parametre = new Parametre();
    	$parametre->setConteneur($objet->getParametre());
    	$formparametre = $this->get('form.factory')->create(new ParametreType(),$parametre);

    	if($formparametre->handleRequest($request)->isValid()){

    			$em->persist($parametre);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de parametre d'objet",
                        'description'=>"Ajouter de paramètre : <b><u>".$parametre->GetNom()." Objet :".$objet->getNom()."</u></b> "
                        ));
    		
    		$em->flush();

            if($parametre->getType() == 'Liste'){
                $valeurs = explode(";", $parametre->getListe());
                foreach ($valeurs as $key => $value) {
                    $vp = new ValeurParametre();
                    $vp->setValeur($value);
                    $vp->setParametre($parametre);
                    $em->persist($vp);
                }
                $em->flush();
            }
    		return $this->redirect($this->generateUrl('trc_admin_objets_voir',array('code'=>$objet->getCode())));
    	}

    	//Les parametres
    	$dql   = "SELECT p FROM TRCCoreBundle:Objet\Parametre p JOIN p.conteneur c WHERE c = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$objet->getParametre());
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('parametre', 1),
            5,
            array(
                'pageParameterName'=>'parametre'
                )
        );


        $instance = new Instance();
    	$instance->setObjet($objet);
    	$form = $this->get('form.factory')->create(new InstanceType(),$instance);

    	if($form->handleRequest($request)->isValid()){

    			$em->persist($instance);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout d'instance d'objet",
                        'description'=>"Ajouter d'instance : <b><u>".$instance->GetNom()." Objet :".$objet->getNom()."</u></b> "
                        ));
    		$this->get('trc_core.gu')->creerDossierInstance($instance);
    		$em->flush();

    		return $this->redirect($this->generateUrl('trc_admin_objets_voir',array('code'=>$objet->getCode())));
    	}

    	//Les parametres
    	$dql   = "SELECT i FROM TRCCoreBundle:Objet\Instance i JOIN i.objet c WHERE c = :objet";
        $query = $em->createQuery($dql);
        $query->setParameter('objet',$objet);
        $paginator  = $this->get('knp_paginator');
        $instances = $paginator->paginate(
            $query,
            $request->query->getInt('instance', 1),
            5,
            array(
                'pageParameterName'=>'instance'
                )
        );

        //La documentation
        $document = new Document();
    	$document->setDocumentation($objet->getDocumentation());
    	$formdocument = $this->get('form.factory')->create(new DocumentType(),$document);

    	if($formdocument->handleRequest($request)->isValid()){

    			$em->persist($document);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de document d'objet",
                        'description'=>"Ajouter de document : <b><u>".$document->GetNom()." Objet :".$objet->getNom()."</u></b> "
                        ));
    		
    		$em->flush();

    		return $this->redirect($this->generateUrl('trc_admin_objets_voir',array('code'=>$objet->getCode())));
    	}

    	//Les parametres
    	$dql   = "SELECT d FROM TRCCoreBundle:Objet\Document d JOIN d.documentation c WHERE c = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$objet->getDocumentation());
        $paginator  = $this->get('knp_paginator');
        $documents = $paginator->paginate(
            $query,
            $request->query->getInt('document', 1),
            5,
            array(
                'pageParameterName'=>'document'
                )
        );


        $dql   = "SELECT o FROM TRCCoreBundle:Objet\Objet o JOIN o.parent p WHERE p = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$objet);
        $paginator  = $this->get('knp_paginator');
        $sousobjets = $paginator->paginate(
            $query,
            $request->query->getInt('objet', 1),
            5,
            array(
                'pageParameterName'=>'objet'
                )
        );


        //La liste de décision
        $decision = new Decision();
        if(is_null($objet->getDecisions())){
            $objet->setDecisions(new \TRC\CoreBundle\Entity\Objet\ConteneurDecision());
            $em->flush();
        }
        $decision->setConteneur($objet->getDecisions());

        $formdecision = $this->get('form.factory')->create(new DecisionType(),$decision);

        if($formdecision->handleRequest($request)->isValid()){

                $em->persist($decision);
                 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de décision d'objet",
                        'description'=>"Ajouter de décision : <b><u>".$decision->getNom()." Objet :".$objet->getNom()."</u></b> "
                        ));
            
            $em->flush();

            return $this->redirect($this->generateUrl('trc_admin_objets_voir',array('code'=>$objet->getCode())));
        }

        //Les parametres
        $dql   = "SELECT d FROM TRCCoreBundle:Core\Decision d JOIN d.conteneur c WHERE c = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$objet->getDecisions());
        $paginator  = $this->get('knp_paginator');
        $decisions = $paginator->paginate(
            $query,
            $request->query->getInt('decision', 1),
            5,
            array(
                'pageParameterName'=>'decision'
                )
        );
    	return $this->render('TRCAdminBundle:Objet:voirObjet.html.twig',
        	array('objet'=>$objet,
        		'formparametre'=>$formparametre->createView(),
                'formdocument'=>$formdocument->createView(),
        		'formdecision'=>$formdecision->createView(),
        		'parametres'=>$pagination,
        		'form'=>$form->createView(),
        		'instances'=>$instances,
        		'documents'=>$documents,
                'sousobjets'=>$sousobjets,
                'decisions'=>$decisions
        		));
    }

    public function voirInstanceAction(Request $request,$objet,$code){

    	$em = $this->get('doctrine')->getManager();
    	$objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
    					->findOneByCode($objet);
    		if(is_null($objet))
    			throw new \Exception("Erreur de code : ".$objet, 1);
    	$instance = $em->getRepository('TRCCoreBundle:Objet\Instance')
    					->findOneBy(
    						array(
    							'code'=>$code,
    							'objet'=>$objet
    							),
    						array(),null,1);
    	if(is_null($instance))
    			throw new \Exception("Erreur de code : ".$code, 1);
    	
    	

    	$parametre = new Parametre();
    	$parametre->setConteneur($instance->getParametre());
    	$formparametre = $this->get('form.factory')->create(new ParametreType(),$parametre);

    	if($formparametre->handleRequest($request)->isValid()){

    			$em->persist($parametre);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de parametre d'instance",
                        'description'=>"Ajouter de paramètre : <b><u>".$parametre->GetNom()." Objet :".$instance->getNom()."</u></b> "
                        ));
    		
    		$em->flush();

            if($parametre->getType() == 'Liste'){
                $valeurs = explode(";", $parametre->getListe());
                foreach ($valeurs as $key => $value) {
                    $vp = new ValeurParametre();
                    $vp->setValeur($value);
                    $vp->setParametre($parametre);
                    $em->persist($vp);
                }
                $em->flush();
            }
    		return $this->redirect($this->generateUrl('trc_admin_instance_voir',array('objet'=>$objet->getCode(),'code'=>$code)));
    	}

    	//Les parametres
    	$dql   = "SELECT p FROM TRCCoreBundle:Objet\Parametre p JOIN p.conteneur c WHERE c = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$instance->getParametre());
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('parametre', 1),
            5
        );

      	//La documentation
        $document = new Document();
    	$document->setDocumentation($instance->getDocumentation());
    	$formdocument = $this->get('form.factory')->create(new DocumentType(),$document);

    	if($formdocument->handleRequest($request)->isValid()){

    			$em->persist($document);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de document d'instance",
                        'description'=>"Ajouter de document : <b><u>".$document->GetNom()." Objet :".$instance->getNom()."</u></b> "
                        ));
    		
    		$em->flush();

    		return $this->redirect($this->generateUrl('trc_admin_instance_voir',array('objet'=>$objet->getCode(),'code'=>$code)));
    	}

    	//Les parametres
    	$dql   = "SELECT d FROM TRCCoreBundle:Objet\Document d JOIN d.documentation c WHERE c = :conteneur";
        $query = $em->createQuery($dql);
        $query->setParameter('conteneur',$instance->getDocumentation());
        $paginator  = $this->get('knp_paginator');
        $documents = $paginator->paginate(
            $query,
            $request->query->getInt('document', 1),
            5
        );
    	return $this->render('TRCAdminBundle:Objet:voirInstance.html.twig',
        	array(
        		'objet'=>$objet,
        		'instance'=>$instance,

        		'formparametre'=>$formparametre->createView(),
        		'parametres'=>$pagination,
        		'formdocument'=>$formdocument->createView(),
        		'documents'=>$documents
/*
        		'form'=>$form->createView(),
        		'instances'=>$instances
//*/
        		));
    }
}