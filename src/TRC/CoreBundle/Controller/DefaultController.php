<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Systemes\General\Core;

use Symfony\Component\HttpFoundation\File\File;
class DefaultController extends Controller
{

    public function trdcAction(){

        return $this->render('TRCCoreBundle:Default:trdc.html.twig',
                    array());
    }
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        
        $security = $this->get('security.context');

        /*
        if($security->isGranted('ROLE_ADMIN')){
            return $this->redirect($this->generateUrl('trc_admin_homepage'));
        }else{
            return $this->redirect($this->generateUrl('trc_demat_homepage'));
        }
        //*/
        return $this->render('TRCCoreBundle:Default:index.html.twig',
            array(
                'fonctions'=>array(),
                'entites'=>array(),
                ));
    }

    public function logoutAction(){

        $em = $this->getDoctrine()->getManager();
        $session = new Session();
        $log = null;
        $user = $this->getUser();
        if(!is_null($session->get('log')))
        $log = $em->getRepository('TRCCoreBundle:Log')
                    ->find($session->get('log'));
        if($log !== null){
            $log->setLogOutAt(new \DateTime());
        }

       

        $em->flush();
        return $this->redirect($this->generateUrl('fos_user_security_logout'));
    }

    public function societeAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $societe = new Societe();
        $form = $this->get('form.factory')->create(
            new SocieteType(),$societe
            );
        if($form->handleRequest($request)->isValid()){

            if(is_null($societe->getImage()))
                $societe->setImage(new File('ga/scte/default.jpg'));
            $societe = $this->get('trc_core.gu')->createSociete($societe);
            $em->persist($societe);
            $em->flush();
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Ajout de société',
                        'description'=>"Ajouter la société: <b>".$societe->getNom()." </b>"
                        ));
            return $this->redirect($this->generateUrl('trc_core_societe'));
        }
        $societes = $em->getRepository('TRCCoreBundle:Societe')
                    ->findAll();
        return $this->render('TRCCoreBundle:Default:societes.html.twig',
            array(
                "form"=>$form->createView(),
                "societes"=>$societes
                ));
    }
  

    public function notificationAction($id){

        $em = $this->getDoctrine()->getManager();
        $datas = array();
        try {
            $objet = $em->getRepository('TRCCoreBundle:Notification')
                ->find($id);
            
            if (!is_null($objet)) {
                
                    $objet->setLu(true);
                    $em->flush();
                    $datas['code'] = 1;
                    $datas['message'] = 'Notification recupérée avec succès!';
                    $datas['notification'] = array(
                        'titre'=>$objet->getTitre(),
                        'contenu'=>$objet->getContenu(),
                        'date'=>$objet->getDatenoti()->format('d-m-Y H:i:s')
                        );
            }else{
                $datas['code'] = -1;
                $datas['message'] = 'Erreur de parametres!!!';
            }
        } catch (Exception $e) {
            $datas['code'] = -2;
            $datas['message'] = $e->getMessage();
        }
        

        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function resumeAction($id,$phase,$download = null){

        $em = $this->getDoctrine()->getManager();
        $datas = array();
        $user = $this->getUser();
        $gu = $this->get('trc_core.gu');
        try {
            $ddc = $em->getRepository('TRCCoreBundle:DDC\DDC')
                ->find($id);
            
            if (is_null($ddc)) {
                    die('Erreur de parametres!!!');
            }
            $contenu = "Consultation de la fiche du dossier ".$ddc->getRc();
            if($phase){
                $contenu = "Consultation du résumé du dossier ".$ddc->getRc();
                $phases = array(
                    'CETDOS'=>"Constitution du dossier et Analyse de risques",
                    'CVCA'=>"Validation du chef d'agence",
                    'CVRZ'=>"Validation du responsable de zone",
                    'CABOC'=>"Analyse de risques au niveau du Back Office Crédits",
                    'CVRBOC'=>"Validation et Décision par le responsable du Back Office Crédits",
                    'CCIC'=>"Etude du dossier par le Comité Interne de Crédits",
                    'CVDO'=>"Déblocage par le directeur des Opérations",
                    //'CSCAD'=>"Suivi du dossier"
                    );
                $datas = array();
                foreach ($phases as $key => $titre) {
                   $centre = $em->getRepository('TRCCoreBundle:Central\\'.$key)
                            ->findOneByDdc($ddc);
                    if(!is_null($centre)){
                        $auteur = "Inconnu";
                        $lieu = "Inconnu";
                        $profil = "Inconnu";
                        $decision = 'Inconnu';
                        $commentaire = "";
                        $date = "Du ##/##/#### ##:##:## au ##/##/#### ##:##:##";
                        $image = 'img/default.png';
                        if(!is_null($centre->getFonction())){
                            $fonction = $centre->getFonction();
                            $uti = $gu->getParentActeur($fonction->getActeur());
                            $entite = $gu->getEntite($fonction->getEntite());
                            $auteur = $uti->getPrenom()." ".strtoupper($uti->getNom());
                            if($uti->getCompte() == $user)
                                $auteur .= " (Vous)";
                            $image = $uti->getImage();
                            $lieu = $entite->getNom();
                            if(!is_null($fonction->getProfil()))
                            $profil = $fonction->getProfil()->getNom()." #".$fonction->getProfil()->getCode();
                        }
                        if(method_exists($centre, 'getDecision') ){

                            if (!is_null($centre->getDecision())) {
                                $decision = $centre->getDecision()->getNom();
                                $commentaire = $centre->getCommentaire();
                                if(!is_null($centre->getDatedebut()) && !is_null($centre->getDatefin())){
                                    $date = 'Du '.$centre->getDatedebut()->format('d/m/Y H:i:s').' au '.$centre->getDatefin()->format('d/m/Y H:i:s');
                                }
                            }else{
                                $titre .= ' <small class="text-danger">En cours...</small>';
                            }

                            
                        }else{
                            $titre .= ' <small class="text-danger">En cours...</small>';
                        }
                        $duree = array();
                        if(method_exists($centre, 'duree') ){
                            $duree = $centre->duree();
                        }
                        $datas[] = array(
                            'auteur'=>$auteur,
                            'titre'=>$titre,
                            'lieu'=>$lieu,
                            'profil'=>$profil,
                            'commentaire'=>$commentaire,
                            'decision'=>$decision,
                            'image'=>$image,
                            'date'=>$date,
                            "duree"=>$duree
                            );
                    }
                }

                if(!is_null($download)){

                    $donnees = array();
                    $colonnes = array(
                            'auteur','phase','lieu','profil','commentaire','decision','date',"duree"
                            );
                    $ddd = array();
                foreach ($phases as $key => $titre) {
                   $centre = $em->getRepository('TRCCoreBundle:Central\\'.$key)
                            ->findOneByDdc($ddc);
                    if(!is_null($centre)){
                        $auteur = "Inconnu";
                        $lieu = "Inconnu";
                        $profil = "Inconnu";
                        $decision = 'Inconnu';
                        $commentaire = "";
                        $date = "Du ##/##/#### ##:##:## au ##/##/#### ##:##:##";
                        $image = 'img/default.png';
                        if(!is_null($centre->getFonction())){
                            $fonction = $centre->getFonction();
                            $uti = $gu->getParentActeur($fonction->getActeur());
                            $entite = $gu->getEntite($fonction->getEntite());
                            $auteur = $uti->getPrenom()." ".strtoupper($uti->getNom());
                            
                            $lieu = $entite->getNom();
                            if(!is_null($fonction->getProfil()))
                            $profil = $fonction->getProfil()->getNom()." #".$fonction->getProfil()->getCode();
                        }
                        if(method_exists($centre, 'getDecision') ){

                            if (!is_null($centre->getDecision())) {
                                $decision = $centre->getDecision()->getNom();
                                $commentaire = $centre->getCommentaire();
                                if(!is_null($centre->getDatedebut()) && !is_null($centre->getDatefin())){
                                    $date = 'Du '.$centre->getDatedebut()->format('d/m/Y H:i:s').' au '.$centre->getDatefin()->format('d/m/Y H:i:s');
                                }
                            }else{
                                $titre .= ' <small class="text-danger">En cours...</small>';
                            }

                            
                        }else{
                            $titre .= ' <small class="text-danger">En cours...</small>';
                        }
                        $duree = array();
                        if(method_exists($centre, 'duree') ){
                            $duree = $centre->duree()['str'];
                        }
                        $ddd[] = array(
                            'auteur'=>$auteur,
                            'titre'=>$titre,
                            'lieu'=>$lieu,
                            'profil'=>$profil,
                            'commentaire'=>$commentaire,
                            'decision'=>$decision,
                            
                            'date'=>$date,
                            "duree"=>$duree
                            );
                    }
                }
                $donnees['colonne'] = $colonnes;
                $donnees['donnees'] = $ddd;
                $export = $this->get('trc_core.export');
                
                /*---------------------------------------
                    Détails du dossier de crédit
                ------------------------------------------*/
                $central = $this->get('trc_core.central');
                $results = array($em->getRepository('TRCCoreBundle:Central\PTEDDC')
                            ->findOneByDdc($ddc));
                $sr = $central->donneesExport($results);
                $sr["phases"] = $donnees;
                /*
                echo "<pre>";
                print_r($sr);
                die('');
                //*/
                return $export->export($this->get('phpexcel'),$sr,$ddc->getRc());
                }
                return $this->render('TRCCoreBundle:Default:lesPhaseDDC.html.twig',
                    array("datas"=>$datas,'ddc'=>$ddc,'id'=>$id,'phase'=>$phase));

            }
            $sysddc = $this->get('trc_core.ddc');
                    $sysddc->enregistrer(
                        array(
                                'user'=>$user,
                                'type'=>null,
                                'contenu'=>$contenu
                                )
                            );
            return $this->render('TRCCoreBundle:Default:resumerddc.html.twig',
            array('ddc'=>$ddc,'id'=>$id,'phase'=>$phase));
        } catch (\Exception $e) {
            //$datas['code'] = -2;
            throw new \Exception("Error : ".$e->getMessage(), 1);
            
        }
        
        /*
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
        //*/
        
    }
    public function supprimerAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $syssgf = $this->get('trc_core.sgf');
        $sysnoti = $this->get('trc_core.noti');
        $sysjournal = $this->get('trc_core.journal');
        $sysgu = $this->get('trc_core.gu');
        $datas = array();
        $datas['reload'] = 0;
        $user = $this->getUser();
        $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->findOneByCompte($user);
        $fonction = $sysgu->getFonction($utilisateur);
        try {
            $id = $request->request->get('id');
            $objet = $request->request->get('objet');
            $datas['code'] = 1;
            
            $object = $em->getRepository($objet)
                    ->find($id);
            $temp = explode("\\", $objet);
            $classe = $temp[count($temp) - 1];

            //notifierDDCMembre
            if($classe == 'FGDDC'){
                $fichier = $object->getFichier();
                $ddc = $object->getDdc();
                $gddc = $object->getGddc();
                
                $supdet = $syssgf->removeFichierDDC($fichier);
                if($supdet['code'] == 1 ){
                    $nbreFgddc = count($em->getRepository($objet)
                    ->findByGddc($gddc));
                    if(!is_null($gddc) && $nbreFgddc == 1 ){
                        $gddc->setCharge(false);
                    }
                    $em->remove($object);
                    $lien = $this->generateUrl('trcddc_consulter',
                        array('rc'=>$ddc->getRc()));
                    $sysnoti->notifierDDCMembre($ddc,$object,$lien,true);

                }
                $datas['message'] = $supdet['message'];
                $datas['code'] = $supdet['code'];
                $datas['reload'] = 1;
                
            }
            elseif($classe == 'FDDC'){
                $fichier = $object->getFichier();
                $ddc = $object->getDdc();
                $docddc = $object->getDocddc();
                
                $supdet = $syssgf->removeFichierDDC($fichier);
                if($supdet['code'] == 1 ){
                    $nbreFgddc = count($em->getRepository($objet)
                    ->findByDocddc($docddc));
                    if(!is_null($docddc) && $nbreFgddc == 1 ){
                        $docddc->setCharge(false);
                    }
                    $em->remove($object);
                    $lien = $this->generateUrl('trcddc_consulter',
                        array('rc'=>$ddc->getRc()));
                    $sysnoti->notifierDDCMembre($ddc,$object,$lien,true);

                }
                $datas['message'] = $supdet['message'];
                $datas['code'] = $supdet['code'];
                $datas['reload'] = 1;
            }elseif($classe == 'MEDP'){

                $supdet = $syssgf->removeFichierDDC($object->getFichier());
                if($supdet['code'] == 1 ){
                    $em->remove($object);
                    $datas['message'] = "Ce post a été supprimé avec succès <br>";
                }
                $datas['message'] .= $supdet['message'];
                $datas['code'] = $supdet['code'];
                $datas['reload'] = 1;
            }elseif($classe == 'DDP'){

                $fonction = $object->getFonction();
                $util = $sysgu->getParentActeur($fonction->getActeur());
                $sysnoti->notifier(
                    array(
                            'acteur'=>$fonction->getActeur(),
                            'titre'=>"Retrait de compétences",
                            "contenu" =>"Vôtre compétence suivante vous a été retirée : <br> ".$object->detail()
                        ));
                $em->remove($object);
                $sysjournal->enregistrer(
                    array(
                            'user'=>$user,
                            'type'=>null,
                            'motcle'=>'Retrait de compétences',
                            'contenu'=>"La compétence suivante vous a été retirée : <br> ".$object->detail().
                                '<h4>'.$util->getPrenom().' '.$util->getNom()
                            )
                        );
                $datas['reload'] = 1;
                $datas['message'] ="<h3>compétence supprimée avec succès </h3> <p>Détails : </p>".$object->detail();
            }elseif($classe == 'Notification'){
                $em->remove($object);
                $sysjournal->enregistrer(
                    array(
                            'user'=>$user,
                            'type'=>null,
                            'motcle'=>'Retrait de compétences',
                            'contenu'=>"Suppression de notification : <br> ".$object->getTitre()." ".$object->getContenu()
                            )
                        );
                $datas['reload'] = 1;
                $datas['message'] ="<h3>La notification  a été supprimée avec succès";   
            }
            //$datas['message'] = $objet;
            $em->flush();
            //$datas['message'] = "Suppression de : ".$objet." possedant l'id : ".$id." de classe : ".$classe." nbreFgddc : ".$nbreFgddc;
        } catch (\Exception $e) {
            $datas['message'] = $e->getMessage();
            $datas['code'] = -1;
        }

        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function choisirEtatAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $sysnoti = $this->get('trc_core.noti');
        $sysgu = $this->get('trc_core.gu');
        $sysddc = $this->get('trc_core.ddc');

        $datas = array();
        try {
            //$object = $request->request->get('objet');
            $user = $this->getUser();
            $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                            ->findOneByCompte($user);
            $id = $request->request->get('id');
            $maFonction = $sysgu->fonction($utilisateur);
            $ddc = $em->getRepository('TRCCoreBundle:DDC\DDC')
                    ->find($id);
            if(is_null($ddc))
                throw new \Exception("Erreur", 1);
            /*
            if(!is_null($ddc->getEtatActuel()))
                throw new \Exception("Le dossier est déjà à une étape de traitement : ".$ddc->getEtatActuel()->getEtat()->getCode()." [<em>".$ddc->getEtatActuel()->getEtat()->getNom()."</em> ]", 1);
            //*/    
            $phase = $ddc->getPhaseActuelle();
            $fetat = $em->getRepository('TRCCoreBundle:DDC\EDDC')
                    ->findOneBy(
                        array("pddc"=>$phase),
                        array(),1,0);
            if(is_null($fetat))
                throw new \Exception("Erreur", 1);
            $a = "";
            if($fetat->getVerdict()){

                $lien = $this->generateUrl('trcddc_decision_commentaire_ddc',
                array('rc'=>$ddc->getRc(),
                    "phase"=>$phase->getPhase()->getCode(),
                    "etat"=>$fetat->getEtat()->getCode()));
                $a = '<a class="btn btn-xs btn-primary" href="'.$lien.'">Statuer et commenter</a>';
            }
            $fprop = $ddc->getFonction();
            $fetat->setFonction($maFonction);
            $fetat->setActive(true);
            $fetat->setDateajout(new \DateTime());
            $ddc->setEtatActuel($fetat);
            $sysnoti->notifier(
                array(
                    "acteur"=>$fprop->getActeur(),
                    "titre"=>"Activation d'étape ",
                    "contenu"=>"L'étape [".$fetat->getEtat()->getNom()."] de votre dossier ".$ddc->getRc()." vient d'être activée par : ".$utilisateur->getPrenom()." ".$utilisateur->getNom()
                    ));

            $em->flush();
            $datas['message'] = "Vous avez activé avec succès l'étape [".$fetat->getEtat()->getNom()."] <br>".$a;
            $datas['code'] = 1 ;
            //$datas['message'] = "Phase : ".$phase->getPhase()->getCode()." etat : ".$fetat->getEtat()->getCode()."<br>".$a;
        } catch (\Exception $e) {
            $datas['code'] = -1;
            $datas['message']= $e->getMessage();

        }   
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function notificationsAction(Request $request,$idNotification = null){

        $notifications = array();
        $notification = null;
        $p = 1;
        $nbre = 5;
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->findOneByCompte($user);
        if(is_null($utilisateur))
            throw new \Exception("Erreur de compte", 1);
            
        $moi = $utilisateur->getActeur();
        $criteres = array("moi"=>$moi); 
        if( 
            $request->query->get('p')!== null 
            && 
            !empty($request->query->get('p'))
          ){
                $p = $request->query->get('p');
        }

        $id = ($p-1)*$nbre;
        if($id < 0)
            $id = 0;
        $sql = 'SELECT n FROM TRCCoreBundle:Notification n WHERE n.acteur = :moi ORDER BY n.lu ASC,n.datenoti DESC';
        $query = $em->createQuery($sql);
        $query->setParameters($criteres);
        $query->setFirstResult($id)->setMaxResults($nbre);

        $notifications = $query->getResult();

        $servicePagination = $this->get('trc_core.pagination');

        $url = $this->generateUrl('trc_core_notifications',array('idNotification'=>$idNotification));
        $urlRoute = 'trc_core_notifications';
       
        if(!is_null($idNotification)){
            $url = $this->generateUrl('trc_core_notifications_voir_une',array('idNotification'=>$idNotification));
            $notification = $em->getRepository('TRCCoreBundle:Notification')
                            ->findOneBy(
                                array('acteur'=>$moi,
                                    'id'=>$idNotification),
                                array(),null,0);
            if(!is_null($notification) && !$notification->getLu()){
                $notification->setLu(true);
                $em->flush();
            }
        }
         $pagination = $servicePagination->pagination2($p,$url,$urlRoute,$criteres,$nbre,$sql);
        return $this->render('TRCCoreBundle:Default:notifications.html.twig',
            array('notifications'=>$notifications,
                'pagination'=>$pagination,
                'notification'=>$notification));
    }
}
