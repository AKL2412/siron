<?php

namespace TRC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Session\Session;
use TRC\CoreBundle\Entity\Param;
use TRC\CoreBundle\Systemes\General\Core;
use TRC\CoreBundle\Entity\Core\MesCles;
class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
    	$session = new Session();
        if(!is_null($session->get('_scte')))
            return $this->redirect($this->generateUrl('trcsdc_homepage',
                array('scte'=>$session->get('_scte')->getCode())));

        return $this->redirect($this->generateUrl('trc_core_homepage'));
    }
    public function rechercheAction(Request $request)
    {
    	$em = $this->get('doctrine')->getManager();
    	$q = $request->request->get('q');
    	$sql = "SELECT DISTINCT p FROM TRCCoreBundle:Procedur p JOIN p.proprietaire pro WHERE p.titre LIKE :q OR p.reference LIKE :q OR pro.nom LIKE :q OR pro.code LIKE :q";

    	$query = $em->createQuery($sql);
    	$query->setParameter('q',"%".$q."%");
    	$procedures = $query->getResult();
        return $this->render('TRCUserBundle:Default:recherche.html.twig',
        	array("procedures"=>$procedures,"q"=>$q));
    }

    public function clepriveAction(Request $request){
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
        $gu = $this->get('trc_core.gu');
        $moi = $gu->getEmploye($user);
        $session = new Session();
        if(is_null($session->get('modifCle')))
            $session->set('modifCle',0);
        if(is_null($moi->getParam())){
            $moi->setParam(new Param());
            $em->flush();
        }
        $param = $moi->getParam();
        if($request->isMethod('POST')){
            $param = $em->getRepository('TRCCoreBundle:Param')
                    ->find($param->getId());
            try {
                $code = 1;
                $message = "";
                $params = $request->request->all();
                if(!array_key_exists('cleactuelle', $params) && !is_null($param->getCleprivee()))
                    throw new \Exception("Vous devez saisir la clé privée actuelle", 1);
                    

                if(array_key_exists('cleactuelle', $params)){
                    $cleactuelle = hash("sha512",$params['cleactuelle']);
                    if($param->getCleprivee() != $cleactuelle){

                        if($session->get('modifCle') < 4){
                            $session->set('modifCle',intval($session->get('modifCle') + 1));
                            throw new \Exception("La clé actuelle saisie est incorrecte. Veuillez réessayer ".intval(5 - $session->get('modifCle')), 1);
                            
                        }else{
                            $moi->getCompte()->setEnabled(false);
                            $em->persist($moi->getCompte());
                            $em->flush();
                            return $this->redirect($this->generateUrl('trc_core_logout'));
                            throw new \Exception("Votre compte de connexion est désactivé", 1);
                            
                        }


                            
                    }
                   
                }
                
                $clenouvelle = hash("sha512",$params['clenouvelle']);
                $clenouvelleconfirmation = hash("sha512",$params['clenouvelleconfirmation']);
                if($clenouvelleconfirmation != $clenouvelle)
                    throw new \Exception("Les clés ne sont pas conformes", 1);
                
                if(! Core::complexiteCle($params['clenouvelle'])){
                    $str = '<ul> <li>Au moins 8 caractères</li>'.
                            '<li>Au moins un caractère spécial</li>'.
                            '<li>Au moins un caractère en majuscule</li>'.
                            '<li>Au moins un chiffre</li></ul>';

                    throw new \Exception("La clé doit être composée : ".$str, 1);
                }

                $mescles = $em->getRepository('TRCCoreBundle:Core\MesCles')
                            ->findOneBy(
                                array(
                                    'cle'=>$clenouvelle,
                                    'employe'=>$moi
                                    ),
                                array(),null,0);
                if(!is_null($mescles))
                    throw new \Exception("Vous avez déjà utilisé cette clé", 1);
                
                $mescles = new MesCles();
                $mescles->setCle($clenouvelle);
                $mescles->setEmploye($moi);
                $param->setCleprivee($clenouvelle);
                $param->setDatemodifcle(new \DateTime());
                $em->persist($param);
                $em->persist($mescles);
                $em->flush();
                $gu->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Mise à jour de clé privée",
                        'description'=>"Modification de la clé privée"
                        ));
                $message = "Clé privée mise à jour avec succès ";
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
        
        return $this->render('TRCUserBundle:Default:cleprive.html.twig',
            array('param'=>$param));
    }
}
