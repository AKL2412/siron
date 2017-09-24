<?php
namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Entity\Alerte;
use TRC\CoreBundle\Form\AlerteType;
use TRC\CoreBundle\Entity\Statut;

class AlerteController extends Controller
{
    public function creerAction(Request $request,$radical=null,$id = null){
        $em = $this->getDoctrine()->getManager();
        $moi = $em->getRepository("TRCCoreBundle:Agent")->findOneByCompte($this->getUser());
        if(is_null($moi))
            throw $this->createAccessDeniedException("Erreur de compte");
       
        
        $alerte = new Alerte();
        $alerte->setAgent($moi);
        if (!is_null($radical)) {
            $client = $em->getRepository('TRCCoreBundle:Client')
            ->findOneByRadical($radical);
            if(is_null($client))
                throw new \Exception("Erreur de paramÃ¨tre #RADICALCLIENT", 1);
            $alerte->setClient($client); 
        }
        
        if (!is_null($id)) {
            $alerte = $em->getRepository('TRCCoreBundle:Alerte')
            ->find($id);
            if(is_null($alerte))
                throw $this->createNotFoundException("Erreur de paramètre ".$id);
                
        }
        if($alerte->getCloture())
            throw new \Exception("Cette alerte n'est plus modifiable", 1);
            
        $form = $this->get('form.factory')->create(
            new AlerteType(),$alerte
            );
        if($form->handleRequest($request)->isValid()){
            if(is_null($id))
                $em->persist($alerte);
           
            
            if(is_null($id)){
                $defaultStatut = $em->getRepository("TRCCoreBundle:StatutAlerte")
                ->findOneByDefaut(true);
                if(is_null($defaultStatut))
                    throw $this->createNotFoundException("Erreur de paramétrage");
                    $statut = new Statut($moi, $alerte, $defaultStatut);
                    $statut->setCommentaire("Ajout de l'alerte");
                    $em->persist($statut);
                $alerte->setStatut($statut);   
                    
            }
            $em->flush();
            return $this->redirect($this->generateUrl('trc_core_voir_alert',array('id'=>$alerte->getId())));
        }
       
        
        return $this->render('TRCCoreBundle:Alerte:index.html.twig',
            array('form'=>$form->createView()));
    }
    
    public function alertesAction(Request $request){
        
        $em = $this->get('doctrine')->getManager();
        $security = $this->get("security.context");
        $moi = $em->getRepository("TRCCoreBundle:Agent")->findOneByCompte($this->getUser());
        if(is_null($moi))
            throw $this->createAccessDeniedException("Erreur de compte");
        $sql = "SELECT a FROM TRCCoreBundle:Alerte a JOIN a.agent ag JOIN a.client cli JOIN a.statut st JOIN st.statut stat JOIN ag.entite ent ";
        $parameters = array();
        if($security->isGranted('ROLE_AS')){
            $sql .= " WHERE a.id > 0 ";
        }elseif($security->isGranted('ROLE_CA')){
            $sql .= " WHERE ent = :entite";
            $parameters['entite'] = $moi->getEntite();
        }else{
            $sql .= " WHERE ag = :agent";
            $parameters['agent'] = $moi;
        }
        $statutSearch = $request->query->get('statut');
        if(!is_null($statutSearch) && $statutSearch != 'tous'){
            $sql .= " and stat.code = :q ";
            $parameters['q'] = $statutSearch;
        }
        $sql .= " ORDER BY a.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameters($parameters);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            16/*limit per page*/
            );
        return $this->render('TRCCoreBundle:Alerte:alertes.html.twig',
            array("alertes"=>$pagination,'statuts'=>$em->getRepository('TRCCoreBundle:StatutAlerte')->findAll(),
                'statutSearch'=>$statutSearch
                )
            );
    }
    
    public function voirAction(Request $request,$id){
        
        $em = $this->get('doctrine')->getManager();
        $security = $this->get("security.context");
        $moi = $em->getRepository("TRCCoreBundle:Agent")->findOneByCompte($this->getUser());
        if(is_null($moi))
            throw $this->createAccessDeniedException("Erreur de compte");
        $alerte = $em->getRepository('TRCCoreBundle:Alerte')->find($id);
        if($security->isGranted('ROLE_AS')){
                
        }elseif( ($security->isGranted('ROLE_CA') && 
            $moi->getEntite() != $alerte->getEntite() ) || ($security->isGranted('ROLE_CC') &&
                $moi->getEntite() != $alerte->getEntite() )
            ){
                throw $this->createAccessDeniedException("Vous ne pouvez pas consulter cette alerte");
            }
        $sql = "SELECT a FROM TRCCoreBundle:Statut a JOIN a.alerte ag WHERE ag = :alerte ORDER BY a.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameters(array('alerte'=>$alerte));
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            16/*limit per page*/
            );
        $statuts = $em->getRepository('TRCCoreBundle:StatutAlerte')
                    ->findByDefaut(false);
        if($request->isMethod('POST')){

            $_statut = $em->getRepository('TRCCoreBundle:StatutAlerte')
                        ->find($request->request->get('statut'));
            $statut = new Statut($moi, $alerte, $_statut);
            $statut->setCommentaire($request->request->get('commentaire'));
            $em->persist($statut);
            $alerte->setStatut($statut); 
            $em->flush();
            return $this->redirect($this->generateUrl('trc_core_voir_alert',array('id'=>$alerte->getId())));
        }
        
        return $this->render('TRCCoreBundle:Alerte:voir.html.twig',
                array("alerte"=>$alerte,'statuts'=>$pagination,'alertestatuts'=>$statuts));
    }
}

