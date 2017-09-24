<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
class AffichageController extends Controller
{
    

    public function presentEmployeAction(\TRC\CoreBundle\Entity\Agent $employe,$recherche = null){
        $gu = $this->get('trc_core.gu');
        return $this->render('TRCCoreBundle:Presentation:presentEmploye.html.twig',
            array("employe"=>$employe,'recherche'=>$recherche));
    }

    public function presentServiceAction(\TRC\CoreBundle\Entity\Entite $service,$recherche = null){
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $postes = count($em->getRepository('TRCCoreBundle:Agent')
                        ->findBy(
                            array(
                                'entite'=>$service
                                ),array(),null,0));
        
        return $this->render('TRCCoreBundle:Presentation:presentService.html.twig',
            array('postes'=>$postes,'recherche'=>$recherche,'service'=>$service));
    }


    public function headerAction()
    {
        return $this->render('TRCCoreBundle:Affichage:header.html.twig');
    }
    public function reglageAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $moi = $gu->getEmploye($this->getUser());
        $onlines = $em->getRepository('TRCCoreBundle:Enligne')->findBy(
            array('online'=>true),array(),null,0);

        return $this->render('TRCCoreBundle:Affichage:reglage.html.twig',
            array('onlines'=>$onlines,'moi'=>$moi));

    }
  

    public function menuAction()
    {
        
        
        return $this->render('TRCCoreBundle:Affichage:menu.html.twig',
            array());
    }
    public function connecteAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $utilisateur = $em->getRepository('TRCCoreBundle:Agent')
                        ->findOneByCompte($user);
        
        return $this->render('TRCCoreBundle:Affichage:connecte.html.twig',
            array());
    }
//\TRC\CoreBundle\Entity\Core\Scenario $Scenario
    public function formulaireTestScenarioAction( array $conditions,$url,$id)
    {
        return $this->render('TRCCoreBundle:Affichage:formulaireTestScenario.html.twig',
            array('conditions'=>$conditions,'url'=>$url,'id'=>$id));
    }
   

    
    public function connectetopAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $agent = $em->getRepository('TRCCoreBundle:Agent')
                        ->findOneByCompte($user);
        
        return $this->render('TRCCoreBundle:Affichage:connectetop.html.twig',
            array('agent'=>$agent));
    }
    public function paginationAction($pagination,$ajax = false,$containerId = null)
    {
        return $this->render('TRCCoreBundle:Affichage:pagination.html.twig', array('pagination' => $pagination,'ajax'=>$ajax,"containerId"=>$containerId));
    }

    public function notificationAction(Request $request)
    {   
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $employe = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($user);
        $nbreNonLus = count(
            $em->getRepository('TRCCoreBundle:Notification')
                ->findBy(
                    array('user'=>$user,'lu'=>false),
                    array(),null,0)
            );
        $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Notification n JOIN n.user u WHERE u = :user and n.trash = false ORDER BY n.lu ASC, n.datenoti DESC';
        $query = $em->createQuery($sql);
        $query->setParameter('user',$user);
        $query->setFirstResult(0)->setMaxResults(10);
        $notis = array(
            'nonlu'=>$nbreNonLus,
            'notis'=>$query->getResult()
            );
        $nbreNonLus = count(
            $em->getRepository('TRCCoreBundle:Message')
                ->findBy(
                    array('receive'=>$user,'lu'=>false),
                    array(),null,0)
            );
        $sql = 'SELECT distinct n FROM TRCCoreBundle:Message n JOIN n.receive u WHERE u = :user ORDER BY n.lu ASC, n.at DESC';
        $query = $em->createQuery($sql);
        $query->setParameter('user',$user);
        $query->setFirstResult(0)->setMaxResults(10);
        $sms = array(
            'nonlu'=>$nbreNonLus,
            'sms'=>$this->get('trc_core.gu')->classerMessage($query->getResult())
            );
        /*
        $dql   = "SELECT p FROM TRCCoreBundle:Point p JOIN p.responsable r JOIN r.employe e JOIN p.etat t WHERE e = :employe AND p.archeve = :archeve ORDER BY t.position ASC";
        $query = $em->createQuery($dql);
        $query->setParameters(array('employe'=>$employe,'archeve'=>false));
        $points = $query->getResult();
        //*/
        /*
        $paginator  = $this->get('knp_paginator');
        $points = $paginator->paginate(
            $query, 
            $request->query->getInt('page', 1),
            10
        );
        //*/
        return $this->render('TRCCoreBundle:Affichage:notification.html.twig', 
            array(
                    "notis"=>$notis,
                    'sms'=>$sms,
                   // "points"=>$points
                ));
    }
}