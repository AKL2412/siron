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

    

   
}
