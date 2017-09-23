<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use TRC\CoreBundle\Entity\Entite;
use TRC\CoreBundle\Form\EntiteType;
use TRC\CoreBundle\Entity\MacroProcessus;
use TRC\CoreBundle\Form\MacroProcessusType;
use TRC\CoreBundle\Entity\Processus;
use TRC\CoreBundle\Form\ProcessusType;
use TRC\CoreBundle\Entity\SousProcessus;
use TRC\CoreBundle\Form\SousProcessusType;
use TRC\CoreBundle\Entity\Procedur;
use TRC\CoreBundle\Form\ProcedurType;
use TRC\CoreBundle\Entity\Type;
use TRC\CoreBundle\Entity\Fichier;
use TRC\CoreBundle\Form\TypeType;
use TRC\CoreBundle\Systemes\General\Core;
class AdminController extends Controller
{
    public function indexAction(Request $request)
    {
    	$em = $this->get('doctrine')->getManager();
    	$entite = new Entite();
    	$type = new Type();
    	$formentite = $this->get('form.factory')->create(new EntiteType(),$entite);
    	$formtype = $this->get('form.factory')->create(new TypeType(),$type);
    	if($formentite->handleRequest($request)->isValid()){
            $em->persist($entite);
            $em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin'));
        }
        if($formtype->handleRequest($request)->isValid()){
        	$type->setDossier("procedures/".$type->getCode());
        	if(!is_dir($type->getDossier()))
				mkdir($type->getDossier(), 0777);
            $em->persist($type);
            $em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin'));
        }
    	$types = $em->getRepository('TRCCoreBundle:Type')->findAll();
    	$entites = $em->getRepository('TRCCoreBundle:Entite')->findAll();
        return $this->render('TRCCoreBundle:Admin:index.html.twig',
        	array(
        		"entites"=>$entites,
        		"types"=>$types,
        		"fentite"=>$formentite->createView(),
        		"ftype"=>$formtype->createView(),
        		));
    }

    public function voirtypeAction(Request $request,$type){
    	$em = $this->get('doctrine')->getManager();
    	$type = $em->getRepository('TRCCoreBundle:Type')
    			->findOneByCode($type);
    	if(is_null($type))
    		throw new \Exception("Error Processing Request", 1);
    	$mp = new MacroProcessus();
    	$mp->setType($type);
    	$formentite = $this->get('form.factory')->create(new MacroProcessusType(),$mp);
    	if($formentite->handleRequest($request)->isValid()){
    		$mp->setDossier($type->getDossier()."/".$mp->getCode());
    		if(!is_dir($mp->getDossier()))
				mkdir($mp->getDossier(), 0777);
            $em->persist($mp);
            $em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin_type',
            	array("type"=>$type->getCode())));
        }
    	$macros = $em->getRepository('TRCCoreBundle:MacroProcessus')
    				->findByType($type);
    	return $this->render('TRCCoreBundle:Admin:voirtype.html.twig',
        	array(
        		"macros"=>$macros,
        		"type"=>$type,
        		"form"=>$formentite->createView()
        		));
    		
    }

    public function voirmacroAction(Request $request,$macro){
    	$em = $this->get('doctrine')->getManager();
    	$type = $em->getRepository('TRCCoreBundle:MacroProcessus')
    			->findOneByCode($macro);
    	if(is_null($type))
    		throw new \Exception("Error Processing Request", 1);
    	$mp = new Processus();
    	$mp->setMacro($type);
    	$formentite = $this->get('form.factory')->create(new ProcessusType(),$mp);
    	if($formentite->handleRequest($request)->isValid()){
    		$mp->setDossier($type->getDossier()."/".$mp->getCode());
    		if(!is_dir($mp->getDossier()))
				mkdir($mp->getDossier(), 0777);
            $em->persist($mp);
            $em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin_type_macro',
            	array("type"=>$type->getType()->getCode(),"macro"=>$type->getCode())));
        }
    	$macros = $em->getRepository('TRCCoreBundle:Processus')
    				->findByMacro($type);
    	return $this->render('TRCCoreBundle:Admin:voirmacro.html.twig',
        	array(
        		"processus"=>$macros,
        		"macro"=>$type,
        		"type"=>$type->getType(),
        		"form"=>$formentite->createView()
        		));
    		
    }
    public function voirprocAction(Request $request,$type,$macro,$proc){
    	$em = $this->get('doctrine')->getManager();
    	$t = $em->getRepository('TRCCoreBundle:Type')
    			->findOneByCode($type);
    	if(is_null($t))
    		throw new \Exception("Erreur de code Type $type", 1);
    	$m = $em->getRepository('TRCCoreBundle:MacroProcessus')
    			->findOneBy(array(
    				"type"=>$t,
    				"code"=>$macro
    				),array(),null,0);
    	if(is_null($m))
    		throw new \Exception("Erreur de code MacroProcessus $macro", 1);

    	$p = $em->getRepository('TRCCoreBundle:Processus')
    			->findOneBy(array(
    				"macro"=>$m,
    				"code"=>$proc
    				),array(),null,0);
    	if(is_null($p))
    		throw new \Exception("Error Processing Request $proc", 1);
    	$mp = new SousProcessus();
    	$mp->setProcessus($p);
    	$formentite = $this->get('form.factory')->create(new SousProcessusType(),$mp);
    	if($formentite->handleRequest($request)->isValid()){
    		$mp->setDossier($p->getDossier()."/".$mp->getCode());
    		if(!is_dir($mp->getDossier()))
				mkdir($mp->getDossier(), 0777);
            $em->persist($mp);
            $em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin_type_macro_proc',
            	array("type"=>$type,"macro"=>$macro,"proc"=>$proc)));
        }
    	$macros = $em->getRepository('TRCCoreBundle:SousProcessus')
    				->findByProcessus($p);
    	return $this->render('TRCCoreBundle:Admin:voirproc.html.twig',
        	array(
        		"sousprocessus"=>$macros,
        		"processus"=>$p,
        		"type"=>$t,
        		"macro"=>$m,
        		"form"=>$formentite->createView()
        		));
    		
    }
    public function voirssprocAction(Request $request,$type,$macro,$proc,$ssproc){
    	$em = $this->get('doctrine')->getManager();
    	$t = $em->getRepository('TRCCoreBundle:Type')
    			->findOneByCode($type);
    	if(is_null($t))
    		throw new \Exception("Erreur de code Type $type", 1);
    	$m = $em->getRepository('TRCCoreBundle:MacroProcessus')
    			->findOneBy(array(
    				"type"=>$t,
    				"code"=>$macro
    				),array(),null,0);
    	if(is_null($m))
    		throw new \Exception("Erreur de code MacroProcessus $macro", 1);

    	$p = $em->getRepository('TRCCoreBundle:Processus')
    			->findOneBy(array(
    				"macro"=>$m,
    				"code"=>$proc
    				),array(),null,0);
    	if(is_null($p))
    		throw new \Exception("Erreur de code processus $proc", 1);

    	$sp = $em->getRepository('TRCCoreBundle:SousProcessus')
    			->findOneBy(array(
    				"processus"=>$p,
    				"code"=>$ssproc
    				),array(),null,0);
    	if(is_null($sp))
    		throw new \Exception("Erreur de code sous-processus $ssproc", 1);
        
        $macros = $this->get('trc_core.gu')->getProcedure($sp)['d'];
    	return $this->render('TRCCoreBundle:Admin:voirssproc.html.twig',
        	array(
        		"procedures"=>$macros,
        		"ssp"=>$sp,
        		"type"=>$t,
        		"macro"=>$m,
        		"processus"=>$p
        		));
    		
    }

    public function voirentitesAction(Request $request,$code){
    	$em = $this->get('doctrine')->getManager();
    	$entite = $em->getRepository('TRCCoreBundle:Entite')
    			->findOneByCode($code);
    	if(is_null($entite))
    		throw new \Exception("Erreur de code entite $code", 1);
    	
    	//*
    	$procedures = $em->getRepository('TRCCoreBundle:Procedur')
    				->findBy(
    					array("proprietaire"=>$entite),
    					array(),null,0
    					);
    	//*/
    	return $this->render('TRCCoreBundle:Admin:voirentites.html.twig',
        	array(
        		"procedures"=>$procedures,
        		"entite"=>$entite,
        		));
    		
    }

    public function ajoutProcedureAction(Request $request,$id = null){

    	$em = $this->get('doctrine')->getManager();
    	$procedure = null;
    	if(!is_null($id)){
    		$procedure = $em->getRepository('TRCCoreBundle:Procedur')->find($id);
    		if(!is_null($procedure))
    			$procedure->setFichier(null);
    	}
    	if(is_null($procedure))
    		$procedure = new Procedur();
    	$form = $this->get('form.factory')->create(new ProcedurType(),$procedure);
    	
    	if($form->handleRequest($request)->isValid()){
    		$file = $form['fichier']->getData();
    		$dossier = "fichiers-procedures/";
    		//echo "<pre>";
    		$em->persist($procedure);
    		if(!is_null($id)){
    			$procedure->setFichier($procedure->getFichier());
    		}

    		if(!is_null($file)){
    			$fichier = new Fichier();
    			$fichier->getType($file->getClientMimeType());
    			$fichier->setNomfichier($file->getClientOriginalName());
    			$newName = Core::slugify(date('dmyHi')."-".$file->getClientOriginalName());
    			$chemin = $dossier.$newName;
    			$fichier->setChemin($chemin);
    			$file->move($dossier, $newName);
    			$fichier->setProcedure($procedure);
    			$em->persist($fichier);
    			$procedure->setFichier($chemin);
    			//print_r($fichier);
    		}

    		
    		//die('');
    		$em->flush();

            return $this->redirect($this->generateUrl('trc_core_admin_ajouter_procedure'));
        }
    	return $this->render('TRCCoreBundle:Admin:ajoutProcedure.html.twig',
        	array(
        		"form"=>$form->createView(),
        		"procedure"=>$procedure
        		));
    }
}