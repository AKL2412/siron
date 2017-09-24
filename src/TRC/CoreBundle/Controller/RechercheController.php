<?php
namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RechercheController extends Controller{
    
    public function indexAction(Request $request){
        
        $q = $request->query->get('q');
        $pagination = $this->getPagination();
        $results = array();
        $nbre = 8;
        $utis = $this->utilisateurs($request,$q,$nbre);
        if(count($utis['d']) > 0 ){
            $results = array_merge($results,$utis['d']);
            if(is_null($pagination))
                $pagination = $utis['p'];
                else{
                    if($pagination->getTotalItemCount() < $utis['p']->getTotalItemCount())
                        $pagination = $utis['p'];
                }
        }
        $utis = $this->entites($request,$q,$nbre);
        if(count($utis['d']) > 0 ){
            $results = array_merge($results,$utis['d']);
            if(is_null($pagination))
                $pagination = $utis['p'];
                else{
                    if($pagination->getTotalItemCount() < $utis['p']->getTotalItemCount())
                        $pagination = $utis['p'];
                }
        }
        
        
        
        $utis = $this->client($request,$q,$nbre);
        if(count($utis['d']) > 0 ){
            $results = array_merge($results,$utis['d']);
            if(is_null($pagination))
                $pagination = $utis['p'];
                else{
                    if($pagination->getTotalItemCount() < $utis['p']->getTotalItemCount())
                        $pagination = $utis['p'];
                }
        }
        
        return $this->render('TRCCoreBundle:Recherche:index.html.twig',
            array('q'=>$q,'results'=>$results,'pagination'=>$pagination));
    }
    private function getPagination(){
        
        $em = $this->get('doctrine')->getManager();
        $sql = "SELECT DISTINCT u FROM TRCCoreBundle:Client u WHERE u.id = 0";
        $query = $em->createQuery($sql);
        //$query->setParameter("q","%".$q."%");
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            1,
            1 /*limit per page*/
            );
        return $pagination;
    }
    private function utilisateurs($request,$q,$nbre){
        $datas = array();
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN'))
            return array(
                'p'=>array(),
                'd'=>array()
            );
            $em = $this->get('doctrine')->getManager();
            $sql = "SELECT DISTINCT u FROM TRCCoreBundle:Agent u WHERE concat(u.prenom,' ',u.nom) LIKE :q OR concat(u.nom,' ',u.prenom) LIKE :q or u.email LIKE :q";
            $query = $em->createQuery($sql);
            $query->setParameter("q","%".$q."%");
            
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                $nbre /*limit per page*/
                );
            $gu = $this->get('trc_core.gu');
            foreach ($pagination as $key => $utili) {
                $datas[] = array(
                    "lien"=>$this->generateUrl('trc_admin_utilisateurs_voir',array('id'=>$utili->getId())),
                    'titre'=>$utili->getPrenom()." ".strtoupper($utili->getNom()),
                    'details'=> "",
                    'objet'=>$utili,
                    "type"=>"Utilisateur"
                );
            }
            return array(
                'p'=>$pagination,
                'd'=>$datas
            );
    }
    
    
    private function entites($request,$q,$nbre){
        $datas = array();
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN'))
            return array(
                'p'=>array(),
                'd'=>array()
            );
            $em = $this->get('doctrine')->getManager();
            $sql = "SELECT DISTINCT u FROM TRCCoreBundle:Entite u WHERE u.nom LIKE :q OR u.code LIKE :q";
            $query = $em->createQuery($sql);
            $query->setParameter("q","%".$q."%");
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                $nbre /*limit per page*/
                );
            foreach ($pagination as $key => $utili) {
                $datas[] = array(
                    "lien"=>$this->generateUrl('trc_admin_services_voir',array('code'=>$utili->getCode())),
                    'titre'=>$utili->getNom(),
                    'details'=>"",
                    'type'=>"Service",
                    'objet'=>$utili,
                );
            }
            return array(
                'p'=>$pagination,
                'd'=>$datas
            );
    }
    
    private function client($request,$q,$nbre){
        $datas = array();
        
            $em = $this->get('doctrine')->getManager();
            $sql = "SELECT DISTINCT u FROM TRCCoreBundle:Client u WHERE u.nom LIKE :q OR u.radical LIKE :q";
            $query = $em->createQuery($sql);
            $query->setParameter("q","%".$q."%");
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                $nbre /*limit per page*/
                );
            foreach ($pagination as $key => $utili) {
                $datas[] = array(
                    "lien"=>$this->generateUrl('trc_core_creer_alerte_avec_client',array('radical'=>$utili->getRadical())),
                    'titre'=>$utili->getPrenom()." ".strtoupper($utili->getNom()),
                    'details'=>"Client :".$utili->getRadical(),
                    'type'=>"Client",
                    'objet'=>$utili,
                );
            }
            return array(
                'p'=>$pagination,
                'd'=>$datas
            );
    }
    
}

