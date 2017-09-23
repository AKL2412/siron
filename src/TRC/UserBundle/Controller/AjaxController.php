<?php

namespace TRC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\DateTime;
use TRC\CoreBundle\Entity\Commentaire;
use TRC\CoreBundle\Entity\Aimer;
use TRC\CoreBundle\Entity\Message;
use TRC\CoreBundle\Entity\Evolution;
use TRC\CoreBundle\Entity\Prorogation;
use TRC\CoreBundle\Entity\Enligne;
use TRC\CoreBundle\Entity\Indisponible;
use TRC\CoreBundle\Entity\Core\MEDP;
use TRC\CoreBundle\Entity\Demat\DematAction;
use TRC\CoreBundle\Entity\Demat\Document;
use TRC\CoreBundle\Systemes\General\Core;

class AjaxController extends Controller
{
	public function indexAction(Request $request)
    {
        if(!$this->get('security.context')->isGranted('ROLE_USER'))
            return $this->redirect($this->generateUrl('trc_core_logout'));
    	$method = $request->request->get('method');
        return $this->$method($request);
    }

    private function mAffecterDemat(Request $request){

        try {

            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $noti = $this->get('trc_core.noti');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            $datas = array();
            $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);

            if (is_null($poste))
                throw new \Exception("Erreur de paramètre #AFFDEM-GUDATA", 1);
            $maCle = $moi->getParam()->getCleprivee();
            if(is_null($maCle))
                throw new \Exception("Veuillez definir votre clé privée d'abord. Merci", 1);
                
            $codeDemat = $request->request->get('code');
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                    ->findOneByCode($codeDemat);
            //#AFFDEM
            if(is_null($demat))
                throw new \Exception("Erreur de paramètre #AFFDEM-GDEMAT", 1);
            
            if(!is_null($demat->getExecuteur())){
                if($demat->getExecuteur() == $poste)
                throw new \Exception("La démat $codeDemat vous est déjà affectée. #AFFDEM-DPEX", 1);
                else
                    throw new \Exception("Vous ne pouvez pas vous affecter cette démat #$codeDemat.Car elle est déjà affectée .#AFFDEM-DPEX", 1);
            }

            if(
                !is_null($demat->getService()) &&
                $demat->getService() != $poste->getService()
                )
                throw new \Exception("Vous ne pouvez pas vous affecter cette démat. Erreur d'entité", 1);
                
            if($demat->getStatuter())
                throw new \Exception("Démat déjà statuée", 1);
            
            $code = 1;
            $message = "";

            //*
            $params = $request->request->all();
            $cleprive = hash("sha512",$params['cleprive']);
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                if(intval($session->get('saisirCle')) == 0 ){
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }
            
            $demat->setExecuteur($poste);
            $demat->setDateaffectation(new \DateTime());
            $demat->setDaterecuperation(null);
            if(is_null($demat->getService()))
                $demat->setService($poste->getService());
            
            
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Affectation de démat",
                        'description'=>"S'affecter la démat  : <b>".$demat->getCode()."</b>"
                        ));
             $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Affectation de démat",
                        'description'=>"Auto affectation",
                        'icon'=>"fa fa-user bg-blue"
                        ));
            $message .= "Affectation effectuée avec succès" ;
            $code = 1; 
           
            //*/
           
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
            //$datas['error'] = $e;
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function statuerDemat(Request $request){

        try {

            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $noti = $this->get('trc_core.noti');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            $datas = array();
            $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);
            //$session->set('saisirCle',3);

            if (is_null($poste))
                throw new \Exception("Erreur de paramètre #VDD-GUDATA", 1);
            $maCle = $moi->getParam()->getCleprivee();
            $codeDemat = $request->request->get('demat');
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                    ->findOneByCode($codeDemat);
            //#VDD
            if(is_null($demat))
                throw new \Exception("Erreur de paramètre #VDD-GDEMAT", 1);
            
            if($poste != $demat->getExecuteur())
                throw new \Exception("Vous n'êtes actuel pas la personne qui travaille sur la démat. Désolé. #VDD-DPEX", 1);
            if($demat->getStatuter())
                throw new \Exception("Démat déjà statuée", 1);
                
            $params = $request->request->all();
            $cleprive = hash("sha512",$params['cleprive']);
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                if(intval($session->get('saisirCle')) == 0 ){
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }
            
            $deamtAction = new DematAction();
            $deamtAction->setDemat($demat);
            $deamtAction->setPosition(
                count(
                    $em->getRepository('TRCCoreBundle:Demat\DematAction')
                            ->findByDemat($demat)
                    ) + 1
                );
            $deamtAction->setScenario($demat->getScenario());
            $deamtAction->setDateaffectation($demat->getDateaffectation());
            $deamtAction->setDaterecuperation($demat->getDaterecuperation());
            $deamtAction->setPoste($poste);
            if(array_key_exists('commentaire', $params))
                $deamtAction->setCommentaire($params['commentaire']);

            if(array_key_exists("fichier", $_FILES)){
                $fichier = $_FILES["fichier"];
                $nomFichier = $demat->getScenario()->getNom()."-".$this->getUser()->getUsername();;
                $sauvegarde = $gu->uploadDematDocument($fichier,$demat,$nomFichier,"decision");
                if($sauvegarde['code'] == -1 )
                    throw new \Exception($sauvegarde['message'], 1);
                $deamtAction->setFichier($sauvegarde['fichier']);
                $message = "Fichier sauvegardé avec succès<br>";
            }

            $decisionValidation = $em->getRepository('TRCCoreBundle:Core\Decision')
                    ->find(intval($params['decision']));
            $deamtAction->setDecision($decisionValidation);

            $em->persist($deamtAction);
            //Actualisation de la demat
            

            $REALISATEUR = $moi->nomprenom();
            $contenuNotification = $demat->getScenario()->getMessage();

            $contenuNotification = str_replace("REALISATEUR", $REALISATEUR, $contenuNotification);
            $contenuNotification = str_replace("DEMAT", $demat->getCode(), $contenuNotification);

            $contenuNotification = str_replace("DECISION",$decisionValidation->getNom(), $contenuNotification);

            
                $demat->setStatuter(true);
                $demat->setDatestatut(new \DateTime());
                $demat->setStatut($decisionValidation);
                $demat->setExecuteur(null);
                $demat->setService(null);
                $demat->setScenario(null);
                $demat->setLibelle($decisionValidation->getNom());

                /*$contenuNotification = "<h2>".$decisionValidation->getNom()."</h2>".$contenuNotification;*/
                $noti->notifier(array(
                    "user"=>$demat->getAuteur()->getEmploye()->getCompte(),
                    "titre"=>"Statut de démat",
                    "contenu"=>$contenuNotification
                    ));
                /*======== ENVOI SMS ===============*/
                $from = $poste->getEmploye()->getTelephone();
                $to = $demat->getAuteur()->getEmploye()->getTelephone();
                if($gu->numFlotte($from) && $gu->numFlotte($to)){
                    $sms = strip_tags($contenuNotification);
                    $r = $gu->sendSMS($from,$to,$sms);
                    $message .= "<p> SMS envoyé à ".$to."<br>".$sms.
                                "<br>Statut : ".$r['status']."<br>Data : ".$r['data']."</p>";
                }
                
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Statut de démat",
                        'description'=>"Décision  : <b>".$decisionValidation->getNom()."</b> sur la démat :<b>".$demat->getCode()."</b><br>Commentaire : ".$params['commentaire']
                        ));
             $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Statut de démat",
                        'description'=>"Décision  : <b>".$decisionValidation->getNom()."</b><p>Commentaire : ".$params['commentaire']."</p>",
                        'icon'=>"fa fa-legal bg-green"
                        ));
            $message .= "Démat statuée" ;
            $code = 1; 
            $datas['lien'] = $this->generateUrl('trc_demat_voir',
                array(
                    'demat'=>$demat->getCode(),
                    'instance'=>$demat->getInstance()->getCode(),
                    'objet'=>$demat->getInstance()->getObjet()->getCode(),
                    ));
           
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
            //$datas['error'] = $e;
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function validerDecisionDemat(Request $request){

        try {

            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $noti = $this->get('trc_core.noti');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            $datas = array();
            $message = "";
            $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);
            //$session->set('saisirCle',3);

            if (is_null($poste))
                throw new \Exception("Erreur de paramètre #VDD-GUDATA", 1);
            $maCle = $moi->getParam()->getCleprivee();
            $codeDemat = $request->request->get('demat');
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                    ->findOneByCode($codeDemat);
            //#VDD
            if(is_null($demat))
                throw new \Exception("Erreur de paramètre #VDD-GDEMAT", 1);
            
            if($poste != $demat->getExecuteur())
                throw new \Exception("Vous n'êtes actuel pas la personne qui travaille sur la démat. Désolé. #VDD-DPEX", 1);
            $params = $request->request->all();
            $nextPoste = $em->getRepository('TRCCoreBundle:Poste')
                    ->find($params['poste']);
            $nextService = $em->getRepository('TRCCoreBundle:Service')
                    ->find($params['service']);
            if(is_null($nextService))
                throw new \Exception("Erreur avec le sélectionné.", 1);

            if(is_null($nextPoste))
                throw new \Exception("Erreur avec l'utilisateur sélectionné.", 1);
            if($nextPoste->getService() != $nextService)
                throw new \Exception($nextPoste->getEmploye()->nomprenom()." ne fait pas partie de <b>".$nextService->getNom(), 1);
                
            $indis = $gu->getIndisponibile($nextPoste->getEmploye());
            if(!is_null($indis))
                throw new \Exception('<b>'.$nextPoste->getEmploye()->nomprenom()." est indisponible </b>".$indis->getRaison().'<hr><p><i class="fa fa-clock-o"></i> Réessayer plus tard </p><p> <i class="fa fa-phone"></i> Passer un coup de fil </p>', 1);
                
            $decisionValidation = $em->getRepository('TRCCoreBundle:Core\Decision')
                    ->find(intval($params['decision']));

            if(is_null($decisionValidation))
                throw new \Exception("Erreur avec la décision sélectionnée", 1);
                
           
            $nextScenarios = $gu->getNextScenario($demat,$nextPoste);
            if(count($nextScenarios) == 0 )
                throw new \Exception("La démat ne peut être acheminée. Aucun scénario correspondant #VDD-PROSCEN", 1);

            if(count($nextScenarios) > 1 )
            throw new \Exception("Existance de chevauchement dans le process défini, Veuillez contacter l'administrateur", 1);
            $nextScenario = $nextScenarios[0];

            $cleprive = hash("sha512",$params['cleprive']);
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                if(intval($session->get('saisirCle')) == 0 ){
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }

            $REALISATEUR = $moi->nomprenom();
            $contenuNotification = $demat->getScenario()->getMessage();

            $contenuNotification = str_replace("REALISATEUR", $REALISATEUR, $contenuNotification);
            $contenuNotification = str_replace("DEMAT", $demat->getCode(), $contenuNotification);
            $contenuNotification = str_replace("DECISION",$decisionValidation->getNom(), $contenuNotification);
            $lien = $this->generateUrl('trc_demat_voir',
                array('demat'=>$demat->getCode(),
                    'instance'=>$demat->getInstance()->getCode(),
                    'objet'=>$demat->getInstance()->getObjet()->getCode(),
                    ));
            $aLink = '<p><a href="'.$lien.'">Voir la démat</a></p>';
            $sms = $contenuNotification;
            $contenuNotification .= $aLink;

            $contenuNotification .= $params['commentaire'];
            //*
            $deamtAction = new DematAction();
            $deamtAction->setDemat($demat);
            $deamtAction->setPosition(
                count(
                    $em->getRepository('TRCCoreBundle:Demat\DematAction')
                            ->findByDemat($demat)
                    ) + 1
                );
            $deamtAction->setScenario($demat->getScenario());
            $deamtAction->setDateaffectation($demat->getDateaffectation());
            $deamtAction->setDaterecuperation($demat->getDaterecuperation());
            $deamtAction->setPoste($poste);
            if(array_key_exists('commentaire', $params))
                $deamtAction->setCommentaire($params['commentaire']);

            if( 
                count($_FILES) > 0 &&
                array_key_exists("fichier", $_FILES) && 
                is_file($_FILES["fichier"]['tmp_name']) &&
                strlen($_FILES["fichier"]['name']) > 0
                ){
                $fichier = $_FILES["fichier"];
                $nomFichier = $demat->getScenario()->getNom()."-".$this->getUser()->getUsername();
                $sauvegarde = $gu->uploadDematDocument($fichier,$demat,$nomFichier,"decision");
                if($sauvegarde['code'] == -1 )
                    throw new \Exception($sauvegarde['message'], 1);
                $deamtAction->setFichier($sauvegarde['fichier']);
                $message .= "Fichier sauvegardé avec succès<br>";
            }

            
            $deamtAction->setDecision($decisionValidation);

            $em->persist($deamtAction);
            //Actualisation de la demat

           // 
            

            
            $nextExecuteur = $nextPoste;
            $nextTexte = "";
            if(!is_null($nextExecuteur) ){
                $demat->setExecuteur($nextExecuteur);
                $demat->setDateaffectation(new \DateTime());
                $demat->setDaterecuperation(null);
                $demat->setService($nextExecuteur->getService());
                $noti->notifier(array(
                    "user"=>$nextExecuteur->getEmploye()->getCompte(),
                    "titre"=>"Réception de démat",
                    "contenu"=>$contenuNotification
                    ));
                /*======== ENVOI SMS ===============*/
                $from = $poste->getEmploye()->getTelephone();
                $to = $nextExecuteur->getEmploye()->getTelephone();
                if($gu->numFlotte($from) && $gu->numFlotte($to)
                    && $nextExecuteur->getEmploye()->getParam()->getSms()
                    ){

                    $sms = strip_tags($params['commentaire'].".DEMAT");
                    $r = $gu->sendSMS($gu->traiterNum($from,'flotte'),
                        $gu->traiterNum($to,'flotte'),$sms);
                    $message .= "<hr><p> SMS envoyé à ".$to."<br>".$sms.
                                "<br>Statut : ".$r['status']."<br>Data : ".$r['data']."</p><hr>";
                }
                /*=========== ENVOI DE MAIL =========================*/

                $from = $poste->getEmploye()->getEmail();
                $to = $nextExecuteur->getEmploye()->getEmail();

                if($nextExecuteur->getEmploye()->getParam()->getMail()){
                    $subject = "Démat ".$demat->getCode();
                    $messageMail = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($from)
                        ->setTo($to)
                        ->setBody($contenuNotification,'text.html');
                        $k = array();
                        if($this->get('mailer')->send($messageMail,$k)){
                            $message .= "<hr> Mail envoyé à ".$to."<br>";
                        }else{
                             $message .= "<hr>Echec envoi de mail  à ".$to." $k <br>";
                        }
                }
                /******************* fin mail ************************/
                $message .= "<p>Démat envoyée à ".$nextExecuteur->getEmploye()->nomprenom()."</p>";
                $nextTexte = "<p>Envoyée à <b>".$nextExecuteur->getEmploye()->nomprenom()."</b></p>";
            }else{
                $demat->setExecuteur(null);
                $demat->setDateaffectation(null);
                $demat->setDaterecuperation(null);
                $demat->setService(null);
            }
            

            $gu->definitionFonctionDemat($demat,$demat->getScenario()->getReceptionnaires());
            $demat->setScenario($nextScenario);
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Prise de décision",
                        'description'=>"Décision  : <b>".$decisionValidation->getNom()."</b> sur la démat :<b>".$demat->getCode()."</b><br>Commentaire : ".$params['commentaire']
                        ));
             $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Prise de décision",
                        'description'=>"Décision  : <b>".$decisionValidation->getNom()."</b><p>Commentaire : ".$params['commentaire']."</p>".$nextTexte,
                        'icon'=>"fa fa-legal bg-green"
                        ));
             //*/
            $message .= "<p>Décision ajoutée</p>" ;
            $code = 1; 
            
           
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
            //$datas['error'] = $e;
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    private function sendFile(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $moi = $gu->getEmploye($this->getUser());
            $monposte = $gu->getMonPoste($moi);

            if(is_null($monposte))
                throw new \Exception("Erreur avec votre compte. #SEFILERCOM", 1);
                
            $datas = array();
            $code = 1;  
            $message = "ok";
            //*
            $temps = array();
            $datas[] = $_FILES;
            $id_demat_document = array_keys($_FILES)[0];
            if($id_demat_document == "fileFichier"){

                $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                ->find(intval($request->request->get('demat')));
                if(is_null($demat))
                    throw new \Exception("Erreur lors de la récuperation de la démat. Erreur sendFile Ajax", 1);
                
                $fichier = $_FILES[$id_demat_document];
                $nomFichier = $request->request->get('nomFichier');
                $datas['fichier nom'] = $nomFichier;
                $datas['demat'] = $demat->getCode();
                $dematDocument = new Document();
                $dematDocument->setDocumentation($demat->getDocumentation());
                $dematDocument->setNom($nomFichier);
                $dematDocument->setProprietaire($monposte);
                $fichier = $_FILES[$id_demat_document];
            
                 $sauvegarde = $gu->uploadDematDocument($fichier,$demat,$nomFichier,"documentation");
            if($sauvegarde['code'] == -1 )
                throw new \Exception($sauvegarde['message'], 1);

            $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            $dematDocument->setFichier($sauvegarde['fichier']);
            //$message = $gu->removeFichier($old)['message'];
            
            $em->persist($dematDocument);
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Chargement de fichier Non paramétré",
                        'description'=>"Charger le fichier : <b>".$dematDocument->getNom()."</b> dans la démat :<b>".$demat->getCode()."</b>"
                        ));
             $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Chargement de fichier Non paramétré",
                        'description'=>"Charger le fichier : <b>".$dematDocument->getNom()."</b>",
                        'icon'=>"fa fa-cloud-upload bg-purple"
                        ));
            $lien = $baseurl."/".$dematDocument->getFichier()->getPath();
            $a = '<a href="'.$lien.'" target="_blank" class="pull-right">'.$dematDocument->getNom().'</a>';
            $datas['lien'] = $a;
            $message = "ok";

            }else{
                
            
            //*
            
            $dematDocument = $em->getRepository('TRCCoreBundle:Demat\Document')
                            ->find($id_demat_document);
            if(is_null($dematDocument))
                throw new \Exception("Une erreur est subvenue lors de la récuperation du document. Erreur sendFile AjaxController", 1);

            
            $old = $dematDocument->getFichier();
            
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                            ->findOneByDocumentation(
                                $dematDocument->getDocumentation()
                                );

            if(is_null($demat))
                throw new \Exception("Une erreur est subvenue lors de la récuperation de la dém@t. Erreur sendFile AjaxController", 1);
            

            $fichier = $_FILES[$id_demat_document];
            
            $sauvegarde = $gu->uploadDematDocument($fichier,$demat,$dematDocument->getDocument()->getNom(),"documentation");
            if($sauvegarde['code'] == -1 )
                throw new \Exception($sauvegarde['message'], 1);

            $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            $dematDocument->setFichier($sauvegarde['fichier']);
            $dematDocument->setProprietaire($monposte);
            $message = $gu->removeFichier($old)['message'];
            

            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Chargement de fichier",
                        'description'=>"Charger le fichier : <b>".$dematDocument->getDocument()->getNom()."</b> dans la démat :<b>".$demat->getCode()."</b>"

                        ));
            $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Chargement de fichier paramétré",
                        'description'=>"Charger le fichier : <b>".$dematDocument->getNom()."</b>",
                        'icon'=>"fa fa-cloud-upload bg-purple"
                        ));
            $lien = $baseurl."/".$dematDocument->getFichier()->getPath();
            $a = '<a href="'.$lien.'" target="_blank" class="pull-right"><i class="fa-file fa"></i>'.$dematDocument->getDocument()->getNom().'</a>';
            $datas['lien'] = $a;
            
            if($gu->estOkDemat($demat))
                $demat->setComplete(true);
            $em->flush();
            $message .= "<br>".$sauvegarde['message'];
            //*/

            }
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    private function submitDematParameter(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $datas = array();
            $code = 1;
            $message = "ok";
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                ->find(intval($request->request->get('demat')));
            if(is_null($demat))
                throw new \Exception("Erreur lors de la récuperation de la démat. Erreur submitDematParameter Ajax", 1);
                
            $messageTrack = "<h5>Renseignement de paramètre : ".$demat->getCode()."</h5><ul>";
            foreach ($request->request->all() as $key => $value) {
                if($key != 'method'){
                    $document = $em->getRepository('TRCCoreBundle:Demat\DematParametreValeur')
                        ->find(intval($key));
                    if(!is_null($document)){

                        /*
                        if(strlen($value) > 0 &&
                            $document->getParametre()->getType() == "Date"){
                            $value = (new \DateTime($value))->format('d/m/Y');
                        }
                        //*/

                        $messageTrack .= "<li><b>".$document->getParametre()->getNom()."</b> : ".$value."</li>";
                        
                        $document->setValeur($value);
                    }
                }
                
            }
            $messageTrack .="</ul>";
            $message = $messageTrack;
            $em->flush();
            if($gu->estOkDemat($demat))
                $demat->setComplete(true);
             $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Renseignement de paramètre de démat",
                        'description'=>$messageTrack
                        ));
             
            $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Renseignement de paramètre",
                        'description'=>$messageTrack,
                        'icon'=>"fa fa-building-o bg-maroon"
                        ));
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function getPosteForServiceByScenario(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $code = 1;
            $message = "ok";
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                        ->findOneByCode($request->request->get('demat'));
            if(is_null($demat))
                throw new \Exception("Erreur avec les paramètres de la démat", 1);
            $service = $em->getRepository('TRCCoreBundle:Service')
                        ->find($request->request->get('service'));
            if(is_null($service))
                throw new \Exception("Erreur avec les paramètres du service", 1);
            
            $scenario = $em->getRepository('TRCCoreBundle:Core\Scenario')
                        ->find($request->request->get('scenario'));

            if(is_null($scenario))
                throw new \Exception("Erreur avec les paramètres du scénario", 1);
            $sql = "SELECT DISTINCT p FROM TRCCoreBundle:Poste p JOIN p.fonction f JOIN p.service s WHERE s = :service AND f in (:fonctions) ";
            $query = $em->createQuery($sql);
            $query->setParameters(
                array('service'=>$service,
                    'fonctions'=>$scenario->getReceptionnaires())
                );
            $postes = $query->getResult();
            $utilisateurs = array();
            foreach ($postes as $key => $p) {
               $utilisateurs[] = array(
                "id"=>$p->getId(),
                "nom"=>$p->getEmploye()->nomprenom(),
                "decision"=>$gu->aDejaDecideDemat($demat,$p)
                );
            }
            $message .= $scenario->getNom();
            $datas['postes'] = $utilisateurs;
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function comptabiliserEcriture(Request $request){

        $datas = array();
        $code = 1;
        $message = "ok";
        $em = $this->get('doctrine')->getManager();
        $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);

        try {
                $gu = $this->get('trc_core.gu');
                $moi = $gu->getEmploye($this->getUser());
            $monposte = $gu->getMonPoste($moi);

            if(is_null($monposte))
                throw new \Exception("Erreur avec votre compte. #SEFILERCOM", 1);
            $maCle = $moi->getParam()->getCleprivee();
            $cleprive = hash("sha512", $request->request->get('cle'));
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        "app"=>"SNA",
                        'action'=>"Tentative d'Execution de comptabilisation",
                        'description'=>"Clé privée érronée"
                        ));
                if(intval($session->get('saisirCle')) == 0 ){
                    $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        "app"=>"SNA",
                        'action'=>"Désactivation de compte",
                        'description'=>"Clé privée érronée 3x"
                        ));
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }
            $aaa = "kel way";
                /**** TRAITEMENT DES PAIEMENT ***************/
                $temps = array();
                $dql   = "SELECT r FROM TRCCoreBundle:SNA\Paiement r  WHERE   r.validateur is not null and r.ecrire = false and r.valide = true";
                $query = $em->createQuery($dql);
                $pnv = $query->getResult();
                foreach ($pnv as $key => $value) {
                    $dossier = $value->getDossier();
                    
                    $dos = $em->getRepository('TRCCoreBundle:SNA\Dossier')
                                ->find($dossier->getId());
                    if(is_null($dos))
                        throw new \Exception("Error Processing Request", 1);
                    $datas['m'] = $dos;
                    $aaa .= $dos->getRacine();
                    $cle = $dos->getRacine();
                    $aaa .= $dos->getAffaire();
                    $nmt = $dos->getCumulFraisEngages() + $value->getMontant();
                    $dos->setCumulFraisEngages($nmt);
                    $temps[$cle] = $nmt;
                    $value->setEcrire(true);
                    $value->setDateecriture(new \DateTime());
                }

                $str = "<h4>Dossiers impactés par le paiement</h4><ul>";
                if(count($temps) == 0)
                    $str = "PAS DE PAIEMENT A COMPTABILISER";
                foreach ($temps as $k => $v) {
                    $str .= "<li>".$k." : ".$v."</li>";
                }
                if(count($temps) > 0)
                $str .= "</ul>";
                $datas['veri'] = $aaa;
                /*========= FIN ===============*/

                /**** TRAITEMENT DES REGLEMENT ***************/
                $temps = array();
                $dql   = "SELECT r FROM TRCCoreBundle:SNA\Reglement r WHERE   r.validateur is not null and r.ecrire = false and r.valide = true";
                $query = $em->createQuery($dql);
                $pnv = $query->getResult();
                foreach ($pnv as $key => $value) {

                    $dossier = $value->getDossier();
                    $cle = $dossier->getRacine();
                    $nmt = $dossier->getCumulReglements() + $value->getMontant();
                    $dossier->setCumulReglements($nmt);
                    $temps[$cle] = $nmt;
                    $value->setEcrire(true);
                    $value->setDateecriture(new \DateTime());
                }

                $str .= "<h4>Dossiers impactés par le règlement</h4><ul>";
                if(count($temps) == 0)
                    $str .= "<br>PAS DE REGLEMENT A COMPTABILISER";
                foreach ($temps as $k => $v) {
                    $str .= "<li>".$k." : ".$v."</li>";
                }
                
                if(count($temps) > 0)
                $str .= "</ul>";
                /*========= FIN ===============*/

                /**** TRAITEMENT DES Provisions ***************/
                $temps = array();
                $dql   = "SELECT r FROM TRCCoreBundle:SNA\Provi r  WHERE   r.validateur is not null and r.ecrire = false and r.valide = true";
                $query = $em->createQuery($dql);
                $pnv = $query->getResult();
                foreach ($pnv as $key => $value) {

                    $dossier = $value->getDossier();
                    $cle = $dossier->getRacine();
                    $nmt = $dossier->getCumulProvisions();
                    if($value->getMvt() == 'CR'){
                        $nmt += $value->getMontant();
                    }elseif($value->getMvt() == 'DB'){
                        $nmt -= $value->getMontant();
                    }
                    
                    $dossier->setCumulProvisions($nmt);
                    $temps[$cle] = $nmt;
                    $value->setEcrire(true);
                    $value->setDateecriture(new \DateTime());
                }
                if(count($temps) > 0)
                $str .= "<h4>Dossiers impactés par la provision</h4><ul>";
                if(count($temps) == 0)
                    $str .= "<br>PAS DE PROVISION A COMPTABILISER";
                foreach ($temps as $k => $v) {
                    $str .= "<li>".$k." : ".$v."</li>";
                }
                if(count($temps) > 0)
                $str .= "</ul>";
                /*========= FIN ===============*/

                if (count($temps))
                $str .= "</ul>";
                $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        "app"=>"SNA",
                        'action'=>"Execution de comptabilisation",
                        'description'=>$str
                        ));
            $message = $str;
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }
        $em->flush();
        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function validerTouteEcrituresEcriture(Request $request){

        $datas = array();
        $code = 1;
        $message = "ok";
        $em = $this->get('doctrine')->getManager();
        $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);

        try {
                $gu = $this->get('trc_core.gu');
                $moi = $gu->getEmploye($this->getUser());
            $monposte = $gu->getMonPoste($moi);

            if(is_null($monposte))
                throw new \Exception("Erreur avec votre compte. #SEFILERCOM", 1);
            $maCle = $moi->getParam()->getCleprivee();
            $cleprive = hash("sha512", $request->request->get('cle'));
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        "app"=>"SNA",
                        'action'=>"Tentative d'Execution de comptabilisation",
                        'description'=>"Clé privée érronée"
                        ));
                if(intval($session->get('saisirCle')) == 0 ){
                    $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        "app"=>"SNA",
                        'action'=>"Désactivation de compte",
                        'description'=>"Clé privée érronée 3x"
                        ));
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }
            /*========== REGLEMENT ================*/
            $dql   = "SELECT r FROM TRCCoreBundle:SNA\Reglement r  WHERE r.dossier is not null AND r.validateur is null and r.ecrire = false";
            $query = $em->createQuery($dql);
            $reglements = $query->getResult();
            $str = count($reglements)." règlement(s) validé(s)<ul>";
            foreach ($reglements as $key => $value) {
                $value->setValide(true);
                $value->setValidateur($monposte);
                $value->setDatevalidation(new \DateTime());
                $str .= "<li>".$value->getNumerooperation()." : ".$value->getMontant()."</li>";
            }
            $str .= "</ul>";
            $message = count($reglements)." règlement(s) validé(s)<br>";

            /*========== PAIEMENT ================*/
            $dql   = "SELECT r FROM TRCCoreBundle:SNA\Paiement r  WHERE r.dossier is not null AND r.validateur is null and r.ecrire = false";
            $query = $em->createQuery($dql);
            $reglements = $query->getResult();
            $str = count($reglements)." paiement(s) validé(s)<ul>";
            foreach ($reglements as $key => $value) {
                $value->setValide(true);
                $value->setValidateur($monposte);
                $value->setDatevalidation(new \DateTime());
                $str .= "<li>".$value->getNumerooperation()." : ".$value->getMontant()."</li>";
            }
            $str .= "</ul>";
            $message .= count($reglements)." paiement(s) validé(s)<br>";

            /*========== PROVISION ================*/
            $dql   = "SELECT r FROM TRCCoreBundle:SNA\Provi r WHERE r.dossier is not null AND r.validateur is null and r.ecrire = false";
            $query = $em->createQuery($dql);
            $reglements = $query->getResult();
            $str = count($reglements)." provision(s) validée(s)<ul>";
            foreach ($reglements as $key => $value) {
                $value->setValide(true);
                $value->setValidateur($monposte);
                $value->setDatevalidation(new \DateTime());
                $str .= "<li>".$value->getNumerooperation()." : ".$value->getMontant()."</li>";
            }
            $str .= "</ul>";
            $message .= count($reglements)." provision(s) validée(s)<br>";

            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Validation de toute écriture',
                        'description'=>$str
                        ));
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }
        $em->flush();
        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    private function validerReglement(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
                
            $code = 1;
            $message = "ok";
            $reglement = $em->getRepository('TRCCoreBundle:SNA\Reglement')
                        ->find(intval($request->request->get('id')));
            if(is_null($reglement))
                throw new \Exception("Erreur avec les paramètres du règlement", 1);
            
            if(!is_null( $reglement->getValidateur()))
                throw new \Exception("Règlement déjà traité", 1);
             $datas['btn'] = '<span class="text-danger"> <i class="fa fa-times"></i> Rejeté</span>';
            if($request->request->get('type') == 'v'){
                $datas['btn'] = '<span class="text-success"> <i class="fa fa-check-circle"></i> Validé</span>';
                $reglement->setValide(true);
            }

            $reglement->setValidateur($poste);
            $reglement->setDatevalidation(new \DateTime());
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Validation de règlement',
                        'description'=>"Dossier  : <b>".$reglement->getDossier()->getCode()." </b><br> <u>".$reglement->getNumerooperation()."</u> : ".$reglement->getMontant()." [".$request->request->get('type')."]"
                        ));
            $message = "Règlement validé <br><b>".$reglement->getNumerooperation()."</b> : ".$reglement->getMontant()." [".$request->request->get('type')."]";
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function validerProvision(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
                
            $code = 1;
            $message = "ok";
            $reglement = $em->getRepository('TRCCoreBundle:SNA\Provi')
                        ->find(intval($request->request->get('id')));
            if(is_null($reglement))
                throw new \Exception("Erreur avec les paramètres de la provision", 1);
            
            if(!is_null( $reglement->getValidateur()))
                throw new \Exception("provision déjà traité", 1);

            $datas['btn'] = '<span class="text-danger"> <i class="fa fa-times"></i> Rejeté</span>';
            if($request->request->get('type') == 'v'){
                $datas['btn'] = '<span class="text-success"> <i class="fa fa-check-circle"></i> Validé</span>';
                $reglement->setValide(true);
            }

            $reglement->setValidateur($poste);
            $reglement->setDatevalidation(new \DateTime());
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Validation de provision',
                        'description'=>"Dossier  : <b>".$reglement->getDossier()->getCode()." </b><br> <u>".$reglement->getNumerooperation()."</u> : ".$reglement->getMontant()."-".$reglement->getMvt()."[".$request->request->get('type')."]"
                        ));
            $message = "Provsion validé <br><b>".$reglement->getNumerooperation()."</b> : ".$reglement->getMontant();
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function validerPaiement(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
                
            $code = 1;
            $message = "ok";
            $reglement = $em->getRepository('TRCCoreBundle:SNA\Paiement')
                        ->find(intval($request->request->get('id')));
            if(is_null($reglement))
                throw new \Exception("Erreur avec les paramètres du paiement", 1);
            
            if(!is_null( $reglement->getValidateur()))
                throw new \Exception("Règlement déjà traité", 1);

            $datas['btn'] = '<span class="text-danger"> <i class="fa fa-times"></i> Rejeté</span>';
            if($request->request->get('type') == 'v'){
                $datas['btn'] = '<span class="text-success"> <i class="fa fa-check-circle"></i> Validé</span>';
                $reglement->setValide(true);
            }

            $reglement->setValidateur($poste);
            $reglement->setDatevalidation(new \DateTime());
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Validation de paiement',
                        'description'=>"Dossier  : <b>".$reglement->getDossier()->getCode()." </b><br> <u>".$reglement->getNumerooperation()."</u> : ".$reglement->getMontant()."[".$request->request->get('type')."]"
                        ));
            $message = "Paiement validé <br><b>".$reglement->getNumerooperation()."</b> : ".$reglement->getMontant();
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function cloturerReunion(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $reunion = $em->getRepository('TRCCoreBundle:Reunion')
                    ->find($id);
        $comite = $reunion->getComite();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        if($gu->secretariatComite($comite,$user)){
            return $this->render('TRCUserBundle:Ajax:cloturerReunion.html.twig',
                array('reunion'=>$reunion));
        }else{
            return new Response('Impossible');
        }

    }
    //#0001
    private function activerCompteConnexion(Request $request){

       try {
            $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $user = $em->getRepository('TRCUserBundle:User')
                    ->find($id);
        $util = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->findOneByCompte($user);
        if(is_null($util))
            throw new \Exception("Erreur de compte #0001", 1);

        if($user->isEnabled()){
            $message = "Le compte a été désactivé avec succès";
            $user->setEnabled(false);
        }else{
            $message = "Le compte  a été activé avec succès";
            $user->setEnabled(true);
            
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'DesActivation de compte de connexion',
                        'description'=>"Message : <b>".$message." </b> <u>".$util->nomprenom()."</u>"
                        ));

       } catch (\Exception $e) {
            $message = $e->getMessage();
       }
        
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function majReglage(Request $request){

       try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $moi = $gu->getEmploye($this->getUser());

            if(is_null($moi))
                throw new \Exception("Erreur avec votre compte. Voir l'administrateur", 1);
                
            $code =  $request->request->get('code');
            $message = "";
            //$code = 1;
            if($code == 'sms'){
                $bok = $moi->getParam()->getSms();
                if($bok){
                    $moi->getParam()->setSms(false);
                    $message = "Paramètre sms désactivé, vous ne recevrez plus de sms lors de la réception d'une démat";
                }else{
                    $moi->getParam()->setSms(true);
                    $message = "Paramètre sms activé, vous  recevrez un sms lors de la réception d'une démat";
                }
            }elseif($code == 'mail'){
                $bok = $moi->getParam()->getMail();
                if($bok){
                    $moi->getParam()->setMail(false);
                    $message = "Paramètre mail désactivé, vous ne recevrez plus de mail lors de la réception d'une démat";
                }else{
                    $moi->getParam()->setMail(true);
                    $message = "Paramètre mail activé, vous  recevrez un mail lors de la réception d'une démat";
                }
            }elseif($code == 'online'){
                $param = $moi->getParam();
                $bok = $param->getOnline();
                if($bok){
                    $param->setOnline(false);
                    $message = "Paramètre disponible désactivé, vous ne serez plus vu en ligne pour la discution instanée démat";
                }else{
                    $param->setOnline(true);
                    $message = "Paramètre disponible activé,vous  serez  vu en ligne pour la discution instanée démat : ".$bok;
                }
                //$em->persist($param);
            }
        
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Mise à jour de paramètre réglage',
                        'description'=>"Message : ".$message
                        ));

       } catch (\Exception $e) {
            $message = $e->getMessage();
       }
        
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
 
    private function reinitialiserMotDePasse(Request $request){

       try {
            $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $user = $em->getRepository('TRCUserBundle:User')
                    ->find($id);
        $util = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->findOneByCompte($user);
        if(is_null($util))
            throw new \Exception("Erreur de compte #0001", 1);
        $motpasse = "a123*123";//hash("sha512",$params['cleprive']);
        
        
        $user->setPlainPassword($motpasse);
            $user->setEnabled(false);
            $em->persist($user);
            $em->flush();
            $util->getParam()->setCleprivee(null);
            $user->setEnabled(true);
            $em->flush();
        $message = "Mot de passe Réinitialisé avec succès.<b>$motpasse</b> ";
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Réinitialisation de mot de passe',
                        'description'=>"Message : <b>".$message." </b> <u>".$util->nomprenom()."</u>"
                        ));

       } catch (\Exception $e) {
            $message = $e->getMessage();
       }
        
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function envoyerCampagne(Request $request){
        $code = 0;
        $message = "Envoi Campagne";
       try {
           $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
            $id =  $request->request->get('code');
        
        $campagne = $em->getRepository('TRCCoreBundle:API\Campagne')
                ->findOneByCode($id);
        if(is_null($campagne))
            throw new \Exception("Erreur de parmètres #0001", 1);

            if($poste != $campagne->getPoste())
                throw new \Exception("Vous ne pouvez pas envoyer cette campagne.Vous n'êtes pas le propriétaire.Merci", 1);
                
        
        $spots = $em->getRepository('TRCCoreBundle:API\Spot')
                ->findByCampagne($campagne);
        $from = "706380312";
        foreach ($spots as $key => $spot) {
            $r = $gu->sendSMS($from,$spot->getNumero(),$spot->getMessage());
            $spot->setStatut($r['status']);
            $spot->setCode($r['code']);
            $spot->setTexte($r['data']);
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Envoi de campagne',
                        'description'=>"Envoi de la campagne ".$campagne->getNom()
                        ));
        $message = "Nombre de sms traités ".count($spots);
        $code = 1;
        $campagne->setEnvoye(true);
        $campagne->setMessage("Campagne envoyée : ".$message);
       } catch (\Exception $e) {
        $code = -1;
        $message = $e->getMessage();
       }
        $em->flush();
        $array["message"] = $message;
        $array["code"] = $code;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function annulerCampagne(Request $request){
        $code = 0;
        $message = "Annuler Campagne";
       try {
           $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
            $id =  $request->request->get('code');
        
        $campagne = $em->getRepository('TRCCoreBundle:API\Campagne')
                ->findOneByCode($id);
        if(is_null($campagne))
            throw new \Exception("Erreur de parmètres #0001", 1);

            if($poste != $campagne->getPoste())
                throw new \Exception("Vous ne pouvez pas annuler cette campagne.Vous n'êtes pas le propriétaire.Merci", 1);
        
        
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Annulation de campagne',
                        'description'=>"Annuler  la campagne ".$campagne->getNom()
                        ));
        $message = "Campagne annulée";
        $campagne->setAnnule(true);
        $campagne->setMessage("Campagne annulée");
       } catch (\Exception $e) {
        $code = -1;
        $message = $e->getMessage();
       }
        $em->flush();
        $array["message"] = $message;
        $array["code"] = $code;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function meRendreDisponible(Request $request){

       try {
            $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        
        $util = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->find($id);
        if(is_null($util))
            throw new \Exception("Erreur de compte #0001", 1);
        $indisponibles = $em->getRepository('TRCCoreBundle:Indisponible')
                    ->findBy(
                        array(
                            'employe'=>$util,
                            "active"=>true,
                            "datefin"=>null
                            ),
                        array(),null,0);
        $str = "";
        foreach ($indisponibles as $key => $indis) {
            $indis->setActive(false);
            $indis->setDatefin(new \DateTime());
            $str .= "<h5>".$indis->getAt()->format('d-m-Y H:i')."</h5>".
                    "<p>".$indis->getRaison()."</p><hr>";
        }
        $util->getParam()->setDisponible(true);
        $message = "Vous êtes maintenant disponible ";
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Rendre disponible',
                        'description'=>$str
                        ));

       } catch (\Exception $e) {
            $message = $e->getMessage();
       }
        
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function desActiveParametre(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Objet\Parametre')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "Le parameter ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "Le parameter ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'DesActivation de paramètre',
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function desActiveScenario(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Core\Scenario')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "Le scénario ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "Le scénario ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'DesActivation de scénario',
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function desActiveDecision(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Core\Decision')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "Le décision ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "Le décision ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'DesActivation de décision',
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function desActiveDocument(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Objet\Document')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "Le document ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "Le document ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'DesActivation de document',
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function desActiveObjet(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Objet\Objet')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "L'objet ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "L'objet ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"DesActivation d'objet",
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    private function desActiveInstance(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id =  $request->request->get('id');
        $parameter = $em->getRepository('TRCCoreBundle:Objet\Instance')
                    ->find($id);
        $etat = "btn-danger";

        if($parameter->getActive()){
            $message = "L'instance ".$parameter->getNom()." a été désactivé avec succès";
            $parameter->setActive(false);
        }else{
            $message = "L'instance ".$parameter->getNom()." a été activé avec succès";
            $parameter->setActive(true);
            $etat = "btn-success";
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"DesActivation d'instance",
                        'description'=>"Message : <b>".$message." </b>"
                        ));
        $array["etat"] = $etat;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    private function sendSMS(Request $request){


        $sendId =  $request->request->get('id');
        $sms = $request->request->get('sms');
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $receive = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($user);
        $send = $em->getRepository('TRCUserBundle:User')->find($sendId);
        $sender = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($send);
        if(is_null($send) || is_null($sender))
            throw new \Exception("Error Processing Request", 1);

        $message = new Message();
        $message->setContenu($sms);
        $message->setSend($user);
        $message->setReceive($send);
      
        $em->persist($message);
        $em->flush();
        return $this->refreshBox($request);
        /*
        $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Message n JOIN n.receive receive JOIN n.send send WHERE (receive = :moi AND send = :lui ) OR (receive = :lui AND send = :moi ) ORDER BY n.at ASC';
        $query = $em->createQuery($sql);
        $query->setParameters(array('moi'=>$user,'lui'=>$send));
        //$query->setFirstResult(0)->setMaxResults(10);
        $messages = $query->getResult();
        return $this->render('TRCUserBundle:Ajax:sendSMS.html.twig', 
            array(
                    'sender'=>$sender,
                    'messages'=>$messages,
                    'moi'=>$receive

                ));
        //*/
    }
    private function readSMS(Request $request){
        $sendId =  $request->request->get('id');
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $receive = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($user);
        $send = $em->getRepository('TRCUserBundle:User')->find($sendId);
        $sender = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($send);
        if(is_null($send) || is_null($sender))
            throw new \Exception("Error Processing Request", 1);

        $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Message n JOIN n.receive receive JOIN n.send send WHERE (receive = :moi AND send = :lui ) OR (receive = :lui AND send = :moi ) ORDER BY n.at ASC';
        $query = $em->createQuery($sql);
        $query->setParameters(array('moi'=>$user,'lui'=>$send));
        //$query->setFirstResult(0)->setMaxResults(10);
        $messages = $query->getResult();
        return $this->render('TRCUserBundle:Ajax:readSMS.html.twig', 
            array(
                    'sender'=>$sender,
                    'messages'=>$messages,
                    'moi'=>$receive

                ));
    }
    private function refreshBox(Request $request){
        $sendId =  $request->request->get('id');
       
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $receive = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($user);
        $send = $em->getRepository('TRCUserBundle:User')->find($sendId);
        $sender = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($send);
        if(is_null($send) || is_null($sender))
            throw new \Exception("Error Processing Request", 1);

        $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Message n JOIN n.receive receive JOIN n.send send WHERE (receive = :moi AND send = :lui ) OR (receive = :lui AND send = :moi ) ORDER BY n.at ASC';
        $query = $em->createQuery($sql);
        $query->setParameters(array('moi'=>$user,'lui'=>$send));
        //$query->setFirstResult(0)->setMaxResults(10);
        $messages = $query->getResult();
        return $this->render('TRCUserBundle:Ajax:sendSMS.html.twig', 
            array(
                    'sender'=>$sender,
                    'messages'=>$messages,
                    'moi'=>$receive

                ));
    }
    private function mesNotification(Request $request){
        $rubrique = $request->request->get('rubrique');

        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $employe = $em->getRepository('TRCCoreBundle:Utilisateur')
                    ->findOneByCompte($user);
        $datas = array();
        $nbreNonLus = 0;
        if($rubrique == "mesSMS"){

            $nbreNonLus = count(
            $em->getRepository('TRCCoreBundle:Message')
                ->findBy(
                    array('receive'=>$user,'lu'=>false),
                    array(),null,0)
            );

            $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Message n JOIN n.receive u WHERE u = :user ORDER BY n.lu ASC, n.at DESC';
            $query = $em->createQuery($sql);
            $query->setParameter('user',$user);
            $query->setFirstResult(0)->setMaxResults(10);
            $datas = $this->get('trc_core.gu')->classerMessage($query->getResult());
            // $query->getResult();
        }elseif($rubrique == "mesNotif"){
            $nbreNonLus = count(
            $em->getRepository('TRCCoreBundle:Notification')
                ->findBy(
                    array('user'=>$user,'lu'=>false,'trash'=>false),
                    array(),null,0)
            );
            $sql = 'SELECT DISTINCT n FROM TRCCoreBundle:Notification n JOIN n.user u WHERE u = :user and n.trash = :trash ORDER BY n.lu ASC, n.datenoti DESC';
            $query = $em->createQuery($sql);
            $query->setParameters(array('user'=>$user,'trash'=>false));
            $query->setFirstResult(0)->setMaxResults(10);
            $datas = $query->getResult();
        }else{

            $onlines = $em->getRepository('TRCCoreBundle:Enligne')->findAll();
            foreach ($onlines as $key => $value) {
                $today = (new \DateTime())->format('d-m-Y H:i:s');
                $val = $value->getLast()->format('d-m-Y H:i:s');
                if(strtotime($today) - strtotime($val) > 60)
                    $value->setOnline(false);
            }
            $myLine = $em->getRepository('TRCCoreBundle:Enligne')
                        ->findOneByUser($user);
            if(is_null($myLine)){
                $myLine = new Enligne();
                $myLine->setUser($user);
                $em->persist($myLine);
            }else{
                $myLine->setLast(new \DateTime());
                $myLine->setOnline(true);
            }

            $em->flush();
            //die('oui');
            return new Response('ok');
        }
        return $this->render('TRCUserBundle:Ajax:notification.html.twig', 
            array(
                    "datas"=>$datas,
                    'nbreNonLus'=>$nbreNonLus,
                    'rubrique'=>$rubrique
                ));
    }
    private function confirmerCloturerReunion(Request $request){

        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $reunion = $em->getRepository('TRCCoreBundle:Reunion')
                    ->find($id);
        $comite = $reunion->getComite();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        if($gu->secretariatComite($comite,$user)){
            $reunion->setCloture(new \DateTime());
            $reunion->setActive(false);
            $em->flush();
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Clôture de réunion',
                        'description'=>"Cloturer la réunion: <b>".$reunion->getCode()." :: ".$comite->getTitre()." </b>"
                        ));
            return new Response('terminer');
        }else{
            return new Response('Impossible');
        }

    }
    private function readNotification(Request $request){
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $user = $this->getUser();
        $noti = $em->getRepository('TRCCoreBundle:Notification')
                    ->find($id);
        if($noti->getUser() == $user){
            $noti->setLu(true);
            if(is_null($noti->getDatelecture()))
                $noti->setDatelecture(new \DateTime());
            $em->flush();
            return $this->render('TRCUserBundle:Ajax:readNotification.html.twig',array('noti'=>$noti));
        }
        else
            return new Response('cette notif ne vous appartient pas');
    }

    private function supprimerMesNotifications(Request $request){
        try {
            $code = 1;
            $em = $this->get('doctrine')->getManager();
            $id = $request->request->get('id');
            $user = $this->getUser();
            $noti = $em->getRepository('TRCCoreBundle:Notification')
                    ->findBy(
                        array(
                            'user'=>$user,
                            'trash'=>false),
                        array(),null,0);
        $message = count($noti)." notification(s) supprimée(s)";
        foreach ($noti as $key => $n) {
            $n->setTrash(true);
        }
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Suppression de notification',
                        'description'=>$message
         ));
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $array["code"] = $code;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    private function likeComite(Request $request)
    {	
    	$em = $this->get('doctrine')->getManager();
    	$id = $request->request->get('id');
    	$comite = $em->getRepository('TRCCoreBundle:Comite')
    				->find($id);

    	$com = new Aimer();
    	$com->setAuteur($this->getUser());
    	$com->setComite($comite);
    	$em->persist($com);
    	$em->flush();
    	$this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Liker  comité',
                        'description'=>"Liker le comité : <b>".$comite->getTitre()." </b>"
         ));


        return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',array('comite'=>$comite,'objet'=>'comite'));
    }
    private function likeReunion(Request $request)
    {   
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $comite = $em->getRepository('TRCCoreBundle:Reunion')
                    ->find($id);

        $com = new Aimer();
        $com->setAuteur($this->getUser());
        $com->setReunion($comite);
        $em->persist($com);
        $em->flush();
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Liker  reunion',
                        'description'=>"Liker le réunion : <b>".$comite->getCode()." </b>"
         ));


        return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',array('reunion'=>$comite,'objet'=>'reunion'));
    }
    private function likePoint(Request $request)
    {   
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $comite = $em->getRepository('TRCCoreBundle:Point')
                    ->find($id);

        $com = new Aimer();
        $com->setAuteur($this->getUser());
        $com->setPoint($comite);
        $em->persist($com);
        $em->flush();
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Liker  action',
                        'description'=>"Liker l'action : <b>".$comite->getCode()." </b>"
         ));


        return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',array('point'=>$comite,'objet'=>'point'));
    }

    private function commentDemat(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        $moi = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->findOneByCompte($user);
        $poste = $gu->getMonPoste($moi);

    	$comment = $request->request->get('comment');
    	$code = $request->request->get('code');
    	$demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
    				->findOneByCode($code);
    	$com = new MEDP();
    	$com->setEdp($demat->getEdp());
    	$com->setPoste($poste);
    	$com->setMessage($comment);
        $message ="";

        if( 
                count($_FILES) > 0 &&
                array_key_exists("fichier", $_FILES) && 
                is_file($_FILES["fichier"]['tmp_name']) &&
                strlen($_FILES["fichier"]['name']) > 0
                ){
                $fichier = $_FILES["fichier"];
                $nomFichier = $demat->getCode()."edp".date('dmYHi');
                $sauvegarde = $gu->uploadDocument($fichier,$demat->getDossier(),$nomFichier,"edp");
                if($sauvegarde['code'] == -1 )
                    throw new \Exception($sauvegarde['message'], 1);
                $com->setFichier($sauvegarde['fichier']);
                $message .= "Fichier sauvegardé avec succès<br>";
            }


    	$em->persist($com);
    	//$em->flush();
    	$gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Commentaire de démat',
                        'description'=>"Commenter la démat : <b>".$demat->getCode().' </b><p class="well">'.$comment."</p>"
         ));

        $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Commentaire",
                        'description'=>'<pre>'.$comment."</pre>",
                        'icon'=>"fa fa-comments bg-aqua"
                        ));
        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:Core\MEDP m JOIN m.edp e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$demat->getEdp());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentDemat.html.twig',array('mes'=>$mes));
    }


    private function commentAvocat(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        $moi = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->findOneByCompte($user);
        $poste = $gu->getMonPoste($moi);

        $comment = $request->request->get('comment');
        $code = $request->request->get('code');
        $avocat = $em->getRepository('TRCCoreBundle:SNA\Avocat')
                    ->findOneByCode($code);
        $com = new \TRC\CoreBundle\Entity\SNA\Commentaire();
        $com->setConteneur($avocat->getConteneurcommentaire());
        $com->setPoste($poste);
        $com->setCommentaire($comment);
        $em->persist($com);
        //$em->flush();
        $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Commentaire d'avocat",
                        'description'=>"Commenter l'avocat : <b>".$avocat->getCode().' </b><p class="">'.$comment."</p>"
         ));

        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:SNA\Commentaire m JOIN m.conteneur e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$avocat->getConteneurcommentaire());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentAvocat.html.twig',array('mes'=>$mes));
    }

    private function getCommentAvocat(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $code = $request->request->get('code');
        $avocat = $em->getRepository('TRCCoreBundle:SNA\Avocat')
                    ->findOneByCode($code);
        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:SNA\Commentaire m JOIN m.conteneur e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$avocat->getConteneurcommentaire());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentAvocat.html.twig',array('mes'=>$mes));
    }

    private function commentDossier(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        $moi = $em->getRepository('TRCCoreBundle:Utilisateur')
                ->findOneByCompte($user);
        $poste = $gu->getMonPoste($moi);

        $comment = $request->request->get('comment');
        $code = $request->request->get('code');
        $avocat = $em->getRepository('TRCCoreBundle:SNA\Dossier')
                    ->findOneByCode($code);
        $com = new \TRC\CoreBundle\Entity\SNA\Commentaire();
        $com->setConteneur($avocat->getConteneurcommentaire());
        $com->setPoste($poste);
        $com->setCommentaire($comment);
        $em->persist($com);
        //$em->flush();
        $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Commentaire de dossier",
                        'description'=>"Commenter l'avocat : <b>".$avocat->getCode().' </b><p class="">'.$comment."</p>"
         ));

        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:SNA\Commentaire m JOIN m.conteneur e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$avocat->getConteneurcommentaire());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentAvocat.html.twig',array('mes'=>$mes));
    }

    private function getCommentDossier(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $code = $request->request->get('code');
        $avocat = $em->getRepository('TRCCoreBundle:SNA\Dossier')
                    ->findOneByCode($code);
        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:SNA\Commentaire m JOIN m.conteneur e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$avocat->getConteneurcommentaire());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentAvocat.html.twig',array('mes'=>$mes));
    }
    private function getCommentDemat(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $code = $request->request->get('code');
        $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                    ->findOneByCode($code);
        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:Core\MEDP m JOIN m.edp e WHERE e = :edp ORDER BY m.at DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('edp',$demat->getEdp());
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:commentDemat.html.twig',array('mes'=>$mes));
    }

    private function getDematTrace(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $code = $request->request->get('code');
        $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                    ->findOneByCode($code);
        $sql = "SELECT DISTINCT m FROM TRCCoreBundle:Demat\DematTrace m JOIN m.demat d WHERE d = :demat  AND m.archive = :archive ORDER BY m.at DESC";
        
        $query = $em->createQuery($sql);
        $query->setParameters(array('demat'=>$demat,'archive'=>false
            ));
        $mes = $query->getResult();

        return $this->render('TRCUserBundle:Ajax:getDematTrace.html.twig',array('traces'=>$mes));
    }

    private function commentReunion(Request $request)
    {
        $comment = $request->request->get('comment');

        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $comite = $em->getRepository('TRCCoreBundle:Reunion')
                    ->find($id);
        $com = new Commentaire();
        $com->setAuteur($this->getUser());
        $com->setReunion($comite);
        $com->setMessage($comment);
        $em->persist($com);
        $em->flush();
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Commentaire de réunion',
                        'description'=>"Commenter la réunion : <b>".$comite->getCode()." </b>"
         ));

        return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',array('reunion'=>$comite,'objet'=>'reunion'));
    }
    private function commentPoint(Request $request)
    {
        $comment = $request->request->get('comment');

        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $comite = $em->getRepository('TRCCoreBundle:Point')
                    ->find($id);
        $com = new Commentaire();
        $com->setAuteur($this->getUser());
        $com->setPoint($comite);
        $com->setMessage($comment);
        $em->persist($com);
        $em->flush();
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Commentaire de point ",
                        'description'=>"Commenter l'action : <b>".$comite->getTitre()." </b> de la réunion <b>".$comite->getReunion()->getCode()."</b>"
         ));

        return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',array('point'=>$comite,'objet'=>'point'));
    }
    public function commentairesComiteAction(\TRC\CoreBundle\Entity\Comite $comite){

    	$em = $this->get('doctrine')->getManager();
    	$like = count($em->getRepository('TRCCoreBundle:Aimer')
    				->findByComite($comite));
    	$comments = $em->getRepository('TRCCoreBundle:Commentaire')
    				->findBy(
    					array(
    						'comite'=>$comite,
    						"trash"=>false),
    					array("at"=>"desc"),null,0);
    	return $this->render('TRCUserBundle:Ajax:commentairesComite.html.twig',array('comite'=>$comite,'likes'=>$like,'comments'=>$comments));
    }
    public function commentairesReunionAction(\TRC\CoreBundle\Entity\Reunion $reunion){

        $em = $this->get('doctrine')->getManager();
        $like = count($em->getRepository('TRCCoreBundle:Aimer')
                    ->findByReunion($reunion));
        $comments = $em->getRepository('TRCCoreBundle:Commentaire')
                    ->findBy(
                        array(
                            'reunion'=>$reunion,
                            "trash"=>false),
                        array("at"=>"desc"),null,0);
        return $this->render('TRCUserBundle:Ajax:commentairesReunion.html.twig',array('reunion'=>$reunion,'likes'=>$like,'comments'=>$comments));
    }
    public function commentairesPointAction(\TRC\CoreBundle\Entity\Point $point){

        $em = $this->get('doctrine')->getManager();
        $like = count($em->getRepository('TRCCoreBundle:Aimer')
                    ->findByPoint($point));
        $comments = $em->getRepository('TRCCoreBundle:Commentaire')
                    ->findBy(
                        array(
                            'point'=>$point,
                            "trash"=>false),
                        array("at"=>"desc"),null,0);
        return $this->render('TRCUserBundle:Ajax:commentairesPoint.html.twig',array('point'=>$point,'likes'=>$like,'comments'=>$comments));
    }

    private function deleteComment(Request $request)
    {
        $comment = $request->request->get('comment');

        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $com = $em->getRepository('TRCCoreBundle:Commentaire')
                    ->find($id);
        if(!is_null($com)){
                $track = "";
                $objet = array();
                if(!is_null($com->getComite())){
                    $objet = array(
                        'objet'=>'comite',
                        'comite'=>$com->getComite()
                        );
                    $track = "Supprimer le commentaire :<br>".$com->getMessage()."<p> Comité ".$com->getComite()->getCode();
                }elseif(!is_null($com->getReunion())){
                    $objet = array(
                        'objet'=>'reunion',
                        'reunion'=>$com->getReunion()
                        );
                    $track = "Supprimer le commentaire :<br>".$com->getMessage()."<p> Réunion ".$com->getReunion()->getCode();
                }elseif(!is_null($com->getPoint())){
                    $objet = array(
                        'objet'=>'point',
                        'point'=>$com->getPoint()
                        );
                     $track = "Supprimer le commentaire :<br>".$com->getMessage()."<p> Action ".$com->getPoint()->getCode();
                }
                $com->setTrash(true);
                $em->flush();
                $this->get('trc_core.gu')->track(array(
                                'user'=>$this->getUser(),
                                'action'=>"Suppression de Commentaire",
                                'description'=>$track
                 ));
                return $this->render('TRCUserBundle:Ajax:actualiserComiteCommentLike.html.twig',$objet);
    }
    throw new \Exception("Error Processing Request", 1);
    
        
    }

    private function refreshTempsPoint(Request $request){
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $point = $em->getRepository('TRCCoreBundle:Point')
                ->find($id);
        return $this->render('TRCUserBundle:Ajax:refreshTempsPoint.html.twig',array('point'=>$point));
    }
    private function debuterPoint(Request $request){
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $point = $em->getRepository('TRCCoreBundle:Point')
                ->find($id);
        $point->setDebuter(true);
        $point->setEtat($em->getRepository('TRCCoreBundle:Etat')
                        ->findOneByCode('ECR'));
        $point->setDatedebut(new \DateTime());
        $evolution = new Evolution();
        $evolution->setCode($this->get('trc_core.gu')->codeEvolution($evolution));
        $evolution->setTitre("Initiation des tâches");
        $evolution->setTaux($em->getRepository('TRCCoreBundle:TauxEvolution')
                        ->findOneByTaux(0));
        $evolution->setCommentaire('Activation du point.');
        $evolution->setPoint($point);
        $em->persist($evolution);
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Activation de point",
                        'description'=>"Activer le point : <b>".$point->getTitre()." </b>"
            ));
        return new Response('<p class="alert alert-success">Point activé.Vous pouvez renseigner les taches (avancements)</p>');
       
    }
    private function supprimerMembreComite(Request $request){
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $membre = $em->getRepository('TRCCoreBundle:Membre')
                ->find($id);
        $membre->setActive(false);
        $message = "Retrait de ".$membre->getEmploye()->getPrenom()." du comite ".$membre->getComite()->getCode()." a été effectué avec succès";
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Retrait de membre de comité",
                        'description'=>$message
            ));
        
        return new Response($message);
       
    }
    private function demandeProrogation(Request $request){
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $gu = $this->get('trc_core.gu');
        $employe = $gu->getEmploye($user);
        $id = $request->request->get('id');
        $msg = $request->request->get('msg');
        $point = $em->getRepository('TRCCoreBundle:Point')
                ->find($id);
        if(!is_null($point) || 
            $point->getResponsable()->getEmploye() != $employe

            ){
            $comite = $point->getReunion()->getComite();
            $reunion = $point->getReunion();
            $progagation = new Prorogation();
            $secretaires = $gu->getMembreSecretariatComite($comite);
            $progagation->setDateactuel($point->getDeadline());
            $link = $this->generateUrl('trcsdc_comite_voir_reunions_voir_point_validation_dp',array(
                "scte"=> $comite->getSociete()->getCode(),
                "slug"=>$comite->getSlug(),
                "code"=>$point->getReunion()->getCode(),
                "pt"=>$point->getCode(),
                "id"=>10
                ));
            $titre = "Demande de prorogation d'échaeance";
            $message = $employe->getPrenom()." demande une prorogation d'échéance concernant son point <em>#".$point->getCode()."</em> <b>".$point->getTitre()."</b> dans le cadre de la reunion <em>".$reunion->getCode()."</em> <b>".$reunion->getTitre()."</b> qui a eu lieu le ".$reunion->getDatereunion()->format('d-m-Y').
            "<p>La date d'échéance actuelle : ".$point->getDeadline()->format('d-m-Y').
            "</p><p>Message :<br>".$msg."</p>";
            if($gu->secretariatComite($comite,$user)){
                $message .= "Presidence notifie";
            }else{
                $message .= "secretaires notifie";
            }
        }else{
            $message = "Erreur! Désolé. #DemandeDeProrogation";
        }
        
        /*
        $point->setDebuter(true);
        $point->setEtat($em->getRepository('TRCCoreBundle:Etat')
                        ->findOneByCode('ECR'));
        $point->setDatedebut(new \DateTime());
        $evolution = new Evolution();
        $evolution->setCode($this->get('trc_core.gu')->codeEvolution($evolution));
        $evolution->setTitre("Initiation des tâches");
        $evolution->setTaux($em->getRepository('TRCCoreBundle:TauxEvolution')
                        ->findOneByTaux(0));
        $evolution->setCommentaire('Activation du point.');
        $evolution->setPoint($point);
        $em->persist($evolution);
        /*
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Retrait de membre de comité",
                        'description'=>$message
            ));
        //*/
        return new Response($message);
       
    }
    private function cloturePoint(Request $request){
        $em = $this->get('doctrine')->getManager();
        $id = $request->request->get('id');
        $point = $em->getRepository('TRCCoreBundle:Point')
                ->find($id);
        $message = "";
        if($point->getTaux() < 100 ){
            $message = "Vous n'avez pas encore atteint les 100% de réalisation.";
        }else{
        $message = '<p class="text-success">Poit achevé avec succès</p>';
        $point->setArcheve(true);
        $point->setEtat($em->getRepository('TRCCoreBundle:Etat')
                        ->findOneByCode('TER'));
        $point->setDatefin(new \DateTime());
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"achèvement de point",
                        'description'=>"Achever le point : <b>".$point->getTitre()." </b>"
            ));
        }
        return new Response($message);
       
    }

    private function getFicheSuivie(Request $request){
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $id = $request->request->get('id');
        $object = $request->request->get('object');

        $colonne = array(
      'Comité','Réunion','Point','Action','description','Niveau','Etat','Date de debut','Echéance','%acheve',"Responsable"
      );
        $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $array = array();
        $code = 0;
        $message = "Object : ".$object." basename : ".$baseurl;
        if($object == "point"){
            $point = $em->getRepository('TRCCoreBundle:Point')
                    ->find($id);
            $donnees = array();
            $donnees[] = $gu->pointToArray($point);
            $datas = array(
                'colonne'=>$colonne,
                "donnees"=>$donnees
                );
            $cheminSaugarde = $point->getDossier();
            $nomfichier = "Fichie de suivi point ".$point->getCode();
            $this->get('trc_core.export')->export(
                $this->get('phpexcel'),
                array("Fiche "=>$datas),
                $nomfichier,
                $cheminSaugarde
                );
            $code = 1;
            $array['lien'] =$baseurl."/".$cheminSaugarde."/".$nomfichier.".xls";
        }elseif($object == "reunion"){
            $reunion = $em->getRepository('TRCCoreBundle:Reunion')
                    ->find($id);
            $points = $em->getRepository('TRCCoreBundle:Point')
                    ->findByReunion($reunion);

            $donnees = array();
            foreach ($points as $key => $point) {
                $donnees[] = $gu->pointToArray($point);
            }
            
            $datas = array(
                'colonne'=>$colonne,
                "donnees"=>$donnees
                );
            $cheminSaugarde = $reunion->getDossier();
            $nomfichier = "Fichie de suivi reunion ".$reunion->getCode();
            $this->get('trc_core.export')->export(
                $this->get('phpexcel'),
                array("Fiche "=>$datas),
                $nomfichier,
                $cheminSaugarde
                );
            $code = 1;
            $array['lien'] =$baseurl."/".$cheminSaugarde."/".$nomfichier.".xls";
        }
        /*
        if($point->getTaux() < 100 ){
            $message = "Vous n'avez pas encore atteint les 100% de réalisation.";
        }else{
        $message = '<p class="text-success">Poit achevé avec succès</p>';
        $point->setArcheve(true);
        $point->setEtat($em->getRepository('TRCCoreBundle:Etat')
                        ->findOneByCode('TER'));
        $point->setDatefin(new \DateTime());
        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"achèvement de point",
                        'description'=>"Achever le point : <b>".$point->getTitre()." </b>"
            ));
        }
        //*/
        $array["code"] = $code;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function modifierMotPasse(Request $request){
        try {
            
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            
            $nouveau = $request->request->get('nouveau');
            $confirmation = $request->request->get('confirmation');
            $code = -1;
            $array = array();
            
            
            
            $code = 1;

            if($nouveau != $confirmation)
                throw new \Exception("Mot de passe incorrect! Les chaines saisies doivent être identiques", 1);
            if(hash('sha512', $nouveau) == $user->getPassword())
            throw new \Exception("Veuillez définir un mot de passe différent de l'actuel. Merci", 1);
            
            $message = "Mot de passe ok";
            //*
            $user->setPlainPassword($nouveau);
            $user->setEnabled(false);
            $em->persist($user);
            $em->flush();

            $user->setEnabled(true);
            $em->flush();
            $code = 2;
            $message = "Modification effectuée avec succès";
            
            $lien = $this->generateUrl('trc_core_logout');
            $array['lien'] = $lien;
            //
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Modification de mot de passe",
                        'description'=>"Modifier mon mot de passe"
            ));
            //*/
        

        } catch (\Exception $e) {
            $code = - 1;
            $message = $e->getMessage();
        }   
       

        $array["code"] = $code;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function rendreIndisponible(Request $request){
        try {
            
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            if($gu->jeSuisDisponible($moi))
                throw new \Exception("Votre dernière indisponibilité est toujours active. Veuillez la clôturer avant de vous rendre indisponible à nouveau", 1);
                
            $maCle = $moi->getParam()->getCleprivee();
            
            $session = new Session();
            if(is_null($session->get('saisirCle')))
                $session->set('saisirCle',3);
            $code = -1;
            $array = array();
            
            $cleprive = hash("sha512",$request->request->get('cleprivee'));
            if($maCle != $cleprive){
                $session->set('saisirCle',intval($session->get('saisirCle') - 1));
                if(intval($session->get('saisirCle')) == 0 ){
                    $moi->getCompte()->setEnabled(false);
                    $em->persist($moi->getCompte());
                    $em->flush();
                    return $this->redirect($this->generateUrl('trc_core_logout'));
                }
                throw new \Exception("Erreur de clé privée! Tentative restant : ".$session->get('saisirCle'), 1);
            }
            $code = 1;

        $indisponible = new Indisponible();
        $indisponible->setRaison($request->request->get('raison'));
        $indisponible->setEmploye($moi);
        $moi->getParam()->setDisponible(false);
        $em->persist($indisponible);

        $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Activation d'indisponibilité",
                        'description'=>"Indisponible : ".$request->request->get('raison')
            ));
        $code = 2;
        $message = "indisponibilité enregistrée avec succès";
        

        } catch (\Exception $e) {
            $code = - 1;
            $message = $e->getMessage();
        }   
       

        $array["code"] = $code;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function supprimerDocumentDemat(Request $request){
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        $id = $request->request->get('id');
        $codeDemat = $request->request->get('code');
        $array = array();
        $message =  "";
        $code = 1;
        try {
            $demat = $em->getRepository('TRCCoreBundle:Demat\Demat')
                        ->findOneByCode($codeDemat);
            //#0002
            if(is_null($demat))
                throw new \Exception("Erreur de paramètre #0002", 1);
            $document = $em->getRepository('TRCCoreBundle:Demat\Document')
                    ->findOneBy(
                        array(
                            'id'=>intval($id),
                            "documentation"=>$demat->getDocumentation()
                            ),
                        array(),null,0);
            //#0003
            if(is_null($document))
                throw new \Exception("Erreur de paramètre #0003", 1);
            $fichier = $document->getFichier();
            $document->setFichier(null);
            $em->flush();
            $gu->removeFichier($fichier);

            if(is_null($document->getDocument()))
                $em->remove($document);
            if(!$gu->estOkDemat($demat))
                $demat->setComplete(false);
            $message = "Le document <b>".$document->getNom()."</b> a été supprimé avec success ";
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Suppression de fichier",
                        'description'=>"Démat : <b>".$code."</b> <p>".$message."</p>"
            ));

            $gu->dematTrace(array(
                        'user'=>$this->getUser(),
                        'demat'=>$demat,
                        'action'=>"Suppression de fichier",
                        'description'=>"Supprimer le fichier : <b>".$document->getNom()."</b>",
                        'icon'=>"fa fa-trash bg-red"
                        ));
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function supprimerCondition(Request $request){
        $em = $this->get('doctrine')->getManager();
        $gu = $this->get('trc_core.gu');
        $user = $this->getUser();
        $id = $request->request->get('id');
        $codeDemat = $request->request->get('code');
        $array = array();
        $message =  "";
        $code = 1;
        try {
           
            $condition = $em->getRepository('TRCCoreBundle:Core\Condition')
                    ->find(intval($id));
            //#0003
            if(is_null($condition))
                throw new \Exception("Erreur de paramètre #0003", 1);
            $message = "Condition supprimée avec succès :<br> ".$condition.
            "<p> Scénario : ".$condition->getScenario()->getNom()."</p>".
            "<p> Objet : ".$condition->getScenario()->getObjet()->getNom()."</p>";
            $em->remove($condition);
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Suppression de condition",
                        'description'=>$message
            ));

        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function signature(Request $request){
        try {
            
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);

            $idEmploye = $request->request->get('idEmploye');
            $employe = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->find(intval($idEmploye));

            if(is_null($employe))
                throw new \Exception("Erreur de paramètre #SIGNRECEMP", 1);

            if(!is_null($employe->getSignature())){
                $old = $employe->getSignature();
                $employe->setSignature(null);
                $em->flush();
                $gu->removeFichier($old);
            }
           $message = "ok";
           $code = 1;
           $array['fichier'] = $_FILES;

           $fichier = $_FILES['signature'];
            
            $nomFichier = $employe->getCode()."signature";
            $sauvegarde = $gu->uploadDocument($fichier,$employe->getDossier(),$nomFichier,"signature");
            if($sauvegarde['code'] == -1 )
                throw new \Exception($sauvegarde['message'], 1);
            $employe->setSignature($sauvegarde['fichier']);

           //* 
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Mise à jour de fichier de signature",
                        'description'=>" Signature de ".$employe->nomprenom()
            ));
        $code = 2;
        $message = "Fichier de signature enregistré avec succès";
        
        //*/
        } catch (\Exception $e) {
            $code = - 1;
            $message = $e->getMessage();
        }   
       

        $array["code"] = $code;
        $array["message"] = $message;
        $response = new Response();
        $response->setContent(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
       
    }

    private function supprimerAssocie(Request $request){

        try {
            $em = $this->get('doctrine')->getManager();
            $gu = $this->get('trc_core.gu');
            $user = $this->getUser();
            $moi = $gu->getEmploye($user);
            $poste = $gu->getMonPoste($moi);
            if(is_null($poste))
                throw new \Exception("Erreur avec votre compte.", 1);
                
            $code = 1;
            $message = "ok";
            $associe = $em->getRepository('TRCCoreBundle:SNA\Associe')
                        ->find(intval($request->request->get('id')));
            if(is_null($associe))
                throw new \Exception("Erreur avec les paramètres de l'associé", 1);
            
            $em->remove($associe);
            $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Suppression d'associé",
                        'description'=>"Supprimer l'associé  : <b>".$associe->intitule()." </b><br> <u> Avocat :".$associe->getAvocat()->intitule()."</u> : "
                        ));
            $message =$associe->intitule()." supprimé avec succès";
            
        } catch (\Exception $e) {
            $code = -1;
            $message = $e->getMessage();
        }

        $datas['code'] = $code;
        $datas['message'] = $message;
        $response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

}