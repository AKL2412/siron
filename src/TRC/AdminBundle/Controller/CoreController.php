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

use TRC\CoreBundle\Entity\Objet\Instance;
use TRC\CoreBundle\Entity\Objet\Documentation;
use TRC\CoreBundle\Entity\Objet\Document;
use TRC\CoreBundle\Form\Objet\InstanceType;
use TRC\CoreBundle\Form\Objet\DocumentType;

use TRC\CoreBundle\Form\Core\ScenarioType;
use TRC\CoreBundle\Entity\Core\Scenario;

use TRC\CoreBundle\Form\Core\ConditionType;
use TRC\CoreBundle\Entity\Core\Condition;

class CoreController extends Controller{

	public function decisionsAction(Request $request,$id=null){

    	$em = $this->get('doctrine')->getManager();
    	$decision = new Decision();

        if (!is_null($id)) {
            $decision = $em->getRepository('TRCCoreBundle:Core\Decision')
                        ->find($id);
            if(is_null($decision))
                throw new \Exception("L'identifiant $id n'est pas correct", 1);
                
        }
    	$form = $this->get('form.factory')->create(new DecisionType(),$decision);

    	if($form->handleRequest($request)->isValid()){

            if(is_null($id)){
    		  $em->persist($decision);
    			 $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de décision",
                        'description'=>"Ajouter de décision : <b><u>".$decision->GetNom()."</u></b> "
                        ));
            }
            $em->flush();

    		return $this->redirect($this->generateUrl('trc_admin_decisions'));
    	}
    	//Les parametres
    	$dql   = "SELECT p FROM TRCCoreBundle:Core\Decision p ";
        $query = $em->createQuery($dql);
        //$query->setParameter('conteneur',$instance->getParametre());
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
    	return $this->render('TRCAdminBundle:Core:decisions.html.twig',
        	array(
        		'pagination'=>$pagination,
        		'form'=>$form->createView()
        		));
    }
    public function processAction(Request $request,$objet,$id=null){

    	$em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $code = $objet;
    	$objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
    					->findOneByCode($objet);
    		if(is_null($objet))
    			throw new \Exception("Erreur de code : ".$code, 1);
        $scenario = null;
        if(!is_null($id))
            $scenario = $em->getRepository('TRCCoreBundle:Core\Scenario')
                    ->find($id);
        if(is_null($scenario)){
            $scenario = new Scenario();
            $scenario->setObjet($objet);
        }
        $form = $this->get('form.factory')->create(new ScenarioType(),$scenario,array('objet'=>$objet));
        
        if($form->handleRequest($request)->isValid()){

            if(is_null($id)){
            $scenario->setCode($gu->codeScenario($objet));
            $em->persist($scenario);
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de scénario",
                        'description'=>"Ajouter le scénario: <b><u>".$scenario->getCode()."</u></b> "
                        ));
            }
            $em->flush();
            return $this->redirect($this->generateUrl('trc_admin_process',array('objet'=>$objet->getCode())));
        }
        //Les parametres
        $dql   = "SELECT p FROM TRCCoreBundle:Core\Scenario p JOIN p.objet o WHERE o = :objet";
        $query = $em->createQuery($dql);
        $query->setParameter('objet',$objet);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
    	return $this->render('TRCAdminBundle:Core:process.html.twig',
        	array(
        		'objet'=>$objet,
                'form'=>$form->createView(),
                'pagination'=>$pagination
        		));
    }

    public function scenarioAction(Request $request,$objet,$code){
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $cod = $objet;
        $objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
                        ->findOneByCode($objet);
            if(is_null($objet))
                throw new \Exception("Erreur de code : ".$cod, 1);
        $scenario = $em->getRepository('TRCCoreBundle:Core\Scenario')
                        ->findOneBy(
                            array(
                                'code'=>$code,
                                'objet'=>$objet
                                ),
                            array(),null,0
                            );
            if(is_null($scenario))
                throw new \Exception("Erreur de code : ".$code, 1);
        /*
        $nomRealisateur = "Kouassi Léon ABY";
        $message = str_replace("REALISATEUR", $nomRealisateur, $scenario->getMessage());
        echo $message;
        die('');
        //*/
        $conteneurs = $objet->mesConteneursParametres();
        $condition = new Condition();
        $condition->setScenario($scenario);
        $form = $this->get('form.factory')->create(new ConditionType(),$condition,array('conteneurParametres'=>$conteneurs));

        if($form->handleRequest($request)->isValid()){

            //$scenario->setCode($gu->codeScenario($objet));
            $em->persist($condition);
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout de condition",
                        'description'=>"Ajouter la condition : <p>".$condition." au scénario <b><u>".$scenario->getCode()."</u></b> "
                        ));
            return $this->redirect($this->generateUrl('trc_admin_process_scenario',array('objet'=>$objet->getCode(),'code'=>$code)));
        }
        //Les parametres
        $dql   = "SELECT p FROM TRCCoreBundle:Core\Condition p JOIN p.scenario o WHERE o = :objet";
        $query = $em->createQuery($dql);
        $query->setParameter('objet',$scenario);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
        return $this->render('TRCAdminBundle:Core:scenario.html.twig',
            array(
                'objet'=>$objet,
                'scenario'=>$scenario,
                'form'=>$form->createView(),
                'pagination'=>$pagination
                ));
    }

    public function testConditionAction(Request $request,$objet,$code){

        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $cod = $objet;
        $objet = $em->getRepository('TRCCoreBundle:Objet\Objet')
                        ->findOneByCode($objet);
            if(is_null($objet))
                throw new \Exception("Erreur de code : ".$cod, 1);
        $scenario = $em->getRepository('TRCCoreBundle:Core\Scenario')
                        ->findOneBy(
                            array(
                                'code'=>$code,
                                'objet'=>$objet
                                ),
                            array(),null,0
                            );
            if(is_null($scenario))
                throw new \Exception("Erreur de code : ".$code, 1);

        $dql   = "SELECT p FROM TRCCoreBundle:Core\Condition p JOIN p.scenario o WHERE o = :objet";
        $query = $em->createQuery($dql);
        $query->setParameter('objet',$scenario);
        $conditions = $query->getResult();
        if(count($conditions) <= 0 )
            throw new \Exception("Veuillez d'abord définir les conditions du scénario", 1);
        
        if($request->isMethod('POST')){

            try {
                
            
            $params = $request->request->all();
            /*
            $sce = $em->getRepository('TRCCoreBundle:Core\Scenario')
                    ->find($request->request->get('scenario'));
                    //
            if($sce != $scenario)
                return new Response("ERREUR!");
            //*/
            $array =array();
            //$array[] = $request->request->get('scenario');
            $statut = true;
            foreach ($params as $idParam => $valeurParam) {
                //*
                $p = null;
                if(is_int($idParam))
                $p = $em->getRepository('TRCCoreBundle:Objet\Parametre')
                        ->find($idParam);
               
               if(!is_null($p)){
                $cds = $gu->getConditionByScenarioParametre($scenario,$p);
                $s = $gu->verificationCondition($cds,$valeurParam);
                if(!$s)
                    $statut =false;
                $array[] = array(
                    'p'=>$p->getNom(),
                    's'=>$s,
                    'v'=>$valeurParam
                    );
               }
                
            }
            return $this->render('TRCAdminBundle:Core:resultatTestCondition.html.twig',
            array(
                'params'=>$array,
                'statut'=>$statut
                ));

            } catch (\Exception $e) {
                return new Response($e->getMessage());
            }

        }
        return $this->render('TRCAdminBundle:Core:testCondition.html.twig',
            array(
                'objet'=>$objet,
                'scenario'=>$scenario,
                'conditions'=>$conditions
                ));
    }
}