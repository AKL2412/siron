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
use TRC\CoreBundle\Entity\Recherche;
use TRC\CoreBundle\Form\SocieteType;
use TRC\CoreBundle\Entity\Utilisateur;
use TRC\CoreBundle\Entity\Poste;
use TRC\CoreBundle\Form\UtilisateurType;
use TRC\CoreBundle\Form\PosteType;
use Symfony\Component\HttpFoundation\File\File;

use TRC\CoreBundle\Entity\App;
use TRC\CoreBundle\Form\AppType;

use TRC\CoreBundle\Entity\Service;
use TRC\CoreBundle\Form\ServiceType;
use TRC\CoreBundle\Entity\Fonction;
use TRC\CoreBundle\Form\FonctionType;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('TRCAdminBundle:Default:index.html.twig');
    }
    
    public function ajoutUtilisateurAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $gu = $this->get('trc_core.gu');
        $session = new Session();

        $utilisateur = new Utilisateur();
        if (!is_null($id)) {
            $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                            ->find($id);
            if(is_null($utilisateur))
                throw new \Exception("Erreur de paramètre #MODIFEMPL", 1);
            $tab = explode("@", $utilisateur->getEmail());
            $utilisateur->setEmail($tab[0]);
            $utilisateur->setImage(new File($utilisateur->getImage()));
                
        }
       // die($utilisateur->getImage()->getPathname());
        $session->set('image',$utilisateur->getImage()->getPathname());
        $form = $this->get('form.factory')->create(
            new UtilisateurType(),$utilisateur
            );
        if($form->handleRequest($request)->isValid()){
            $admin = array();
            if(null !== $request->request->get('applications'))
                $admin = $request->request->get('applications');
            
            $tab = explode("@", $utilisateur->getEmail());
            $utilisateur->setEmail($tab[0]."@banqueatlantique.net");

            

            if(is_null($id)){
                if(is_null($utilisateur->getImage()))
                    $utilisateur->setImage($session->get('image'));
            $utilisateur = $this->get('trc_core.gu')->createUtilisateur($utilisateur,$request->request->get('applications'));
            $em->persist($utilisateur);
            $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout d'employé",
                        'description'=>"Ajouter de l'employé: <b><u>".$utilisateur->getMatricule()."</u></b> "
                        ));
            }else{
                if(is_null($utilisateur->getImage()))
                    $utilisateur->setImage($session->get('image'));
                else{
                    $file = new File($utilisateur->getImage());
                    if($file != null){
                            $extension = $file->guessExtension();
                            if (!$extension) {
                                $extension = 'jpg';
                            }
                            $nomImage = $utilisateur->getCode().'-'.date('dmYHis').'.'.$extension;
                            
                                $file->move($utilisateur->getDossier()."/img", $nomImage);
                            
                            $utilisateur->setImage($utilisateur->getDossier()."/img".'/'.$nomImage);
                        }
                }

                if(count($admin) > 0)
                    $utilisateur->setCompte($gu->creerCompte($utilisateur,$admin));
                if(!$utilisateur->getActive()){
                    $p = $gu->getMonPoste($utilisateur);
                     if(!is_null($p))
                    $p->setActive(false);
                    $em->flush();
                }

                if($utilisateur->getTrash()){
                    $utilisateur->setActive(false);
                    $p = $gu->getMonPoste($utilisateur);
                    if(!is_null($p))
                    $p->setActive(false);
                   
                    if(!is_null($utilisateur->getCompte()))
                        $utilisateur->getCompte()->setEnabled(false);
                     $em->flush();
                     $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Suppression d'employé",
                        'description'=>"supprimer l'employé: <b><u>".$utilisateur->getMatricule()."</u></b> "
                        ));
                    return $this->redirect($this->generateUrl('trc_admin_utilisateurs'));
                }
                $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Modification d'employé",
                        'description'=>"Modifier l'employé: <b><u>".$utilisateur->getMatricule()."</u></b> "
                        ));
            }
            
            $em->flush();
            
            return $this->redirect($this->generateUrl('trc_admin_utilisateurs_voir',array('id'=>$utilisateur->getId())));
        }
        
        return $this->render('TRCAdminBundle:Default:admin.html.twig',
            array(
                "form"=>$form->createView()
                ));
    }
    public function utilisateursAction(Request $request){

    	$em = $this->get('doctrine')->getManager();
       $security = $this->get('security.context');
       
       $dql   = "SELECT a FROM TRCCoreBundle:Utilisateur a WHERE a.active = true";
       if($security->isGranted('ROLE_ADMIN'))
        $dql   = "SELECT a FROM TRCCoreBundle:Utilisateur a";
        $query = $em->createQuery($dql);
       // $query->setParameter('societe',$societe);
       // $societes = $query->getResult();
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            16/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:employes.html.twig',
            array("pagination"=>$pagination));
    }
    public function utilisateurVoirAction(Request $request,$id){

    	$em = $this->get('doctrine')->getManager();
       	$security = $this->get('security.context');
       	$employe = $em->getRepository('TRCCoreBundle:Utilisateur')
       					->find($id);

       	if(is_null($employe))
       		throw new \Exception("Erreur lors de la récupération des dinformations de l'utilisateur", 1);
        
       
      
       if(!$security->isGranted('ROLE_ADMIN') && $employe->getTrash())
            throw new \Exception("Employé supprimé", 1);
            
       	$postes = $this->get('trc_core.gu')->getMesPoste($employe);
    	
       	$poste = $this->get('trc_core.gu')->getMonPoste($employe);
        if(is_null($poste)){
            $poste = new Poste();
            $poste->setEmploye($employe);
        }
        
        $form = $this->get('form.factory')->create(
            new PosteType(),$poste);

        if($form->handleRequest($request)->isValid()){

        	

        	$responsable = $this->get('trc_core.gu')->responsableService($poste->getService());
        	if($poste->getFonction()->getResponsable() && !is_null($responsable)){
        		$nomService = $poste->getService()->getNom();
        		$nomResponsable = $responsable->getEmploye()->getPrenom()." ".strtoupper($responsable->getEmploye()->getNom());
        		throw new \Exception("La fonction que vous affectez est une fonction responsable. Elle ne peut exister q'une fois au niveau d'une même entité et actuellement le responsable de ".$nomService." est ".$nomResponsable, 1);
        		
        	}
            $postes = $em->getRepository('TRCCoreBundle:Poste')
                        ->findBy(
                        	array("employe"=>$employe,"active"=>true),
                        	array(),null,0);
            foreach ($postes as $key => $p) {
                $p->setFin(new \DateTime());
                $p->setActive(false);
            }
            $poste->setCode($this->get('trc_core.gu')->codePoste($poste));
            $poste->setActive(true);
            $em->persist($poste);
            $em->flush();
            /*
            $recherche = new Recherche();
            $recherche->setPoste($poste);
            $employe->setActive(true);
            $em->persist($recherche);
            $em->flush();
            //*/
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Affectation d'employé",
                        'description'=>"Affecter l'employé<b>".$employe->getNom()." </b> <u>".$employe->getCode()."</u> à l'entité ".$poste->getService()->getNom().".Fonction <b>".$poste->getFonction()->getNom()."</b>"
                        ));
             //*/
            return $this->redirect($this->generateUrl('trc_admin_utilisateurs_voir',
                array('id'=>$id)));
        }

      

        return $this->render('TRCAdminBundle:Default:employe.html.twig',
            array("employe"=>$employe,'poste'=>$poste,'postes'=>$postes,'form'=>$form->createView(),
                ));
    }

    public function servicesAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $service = null;
        if(!is_null($id))
            $service = $em->getRepository('TRCCoreBundle:Service')
                    ->find($id);

        if(is_null($service))
            $service = new Service();
        $form = $this->get('form.factory')->create(
            new ServiceType(),$service
            );
        if($form->handleRequest($request)->isValid()){

            //$service->setCode($this->get('trc_core.gu')->codeService($service));
            if(is_null($id))
            $em->persist($service);
            $em->flush();
            /*
            if(is_null($id)){
                $recherche = new Recherche();
                $recherche->setService($service);
                $em->persist($recherche);
                $em->flush();
            }
            //*/
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Ajout/modification de service',
                        'description'=>"Entité: <b>".$service->getNom()." </b>"
                        ));
            return $this->redirect($this->generateUrl('trc_admin_services'));
        }
        
        $dql   = "SELECT a FROM TRCCoreBundle:Service a ";
        $query = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            20/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:services.html.twig',
            array(
                "form"=>$form->createView(),
                'pagination' => $pagination
                ));
    }
    public function servicesVoirAction(Request $request,$code){
        $em = $this->getDoctrine()->getManager();
        $entite = $em->getRepository('TRCCoreBundle:Service')
                    ->findOneByCode($code);
        if(is_null($entite))
            throw new \Exception("Erreur de code ".$code, 1);
        $sql = "SELECT DISTINCT p,f FROM TRCCoreBundle:Poste p JOIN p.service s JOIN p.fonction f WHERE p.active = true AND s = :service ORDER BY f.responsable DESC";
        $query = $em->createQuery($sql);
        $query->setParameter('service',$entite);
       // $societes = $query->getResult();
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            15/*limit per page*/
        );
        $sousentites = $em->getRepository('TRCCoreBundle:Service')
                        ->findByParent($entite);
        return $this->render('TRCAdminBundle:Default:servicesVoir.html.twig',
            array(
               // "form"=>$form->createView(),
                'entite' => $entite,
                'postes'=>$pagination,
                'sousentites'=>$sousentites
                ));
            
    }
    public function fonctionsAction(Request $request,$id=null){

        $em = $this->getDoctrine()->getManager();
        $fonction = new Fonction();
       	if(!is_null($id))
       		$fonction = $em->getRepository('TRCCoreBundle:Fonction')
       					->find($id);
       	if(is_null($fonction))
       		throw new \Exception("Erreur! ".$id, 1);
       		
        
        $form = $this->get('form.factory')->create(
            new FonctionType(),$fonction
            );
        if($form->handleRequest($request)->isValid()){

            $em->persist($fonction);
            $em->flush();
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>'Ajout de fonction',
                        'description'=>"Ajouter la fonction: <b>".$fonction->getNom()." </b>"
                        ));
            return $this->redirect($this->generateUrl('trc_admin_fonctions'));
        }
        
        $dql   = "SELECT a FROM TRCCoreBundle:Fonction a ";
        $query = $em->createQuery($dql);
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:fonctions.html.twig',
            array(
                "form" => $form->createView(),
               // "services" => $societes,
                //"scte" => $societe,
                'pagination' => $pagination
                ));
    }

     public function compteAction(Request $request){

        $em = $this->get('doctrine')->getManager();
        $session = new Session();
        $security = $this->get('security.context');
        /*
        if(!$security->isGranted('ROLE_ADMIN'))
            throw new \Exception("ACCES INTERDIT", 1);
        //*/
        $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->findOneBy(array(
                            "compte"=>$this->getUser()
                            ),array(),null,0);
        if(is_null($utilisateur))
            throw new NotFoundHttpException("Erreur avec votre compte. Contacter l'administrateur");
        
            
       
        $session->set('imageUtilisateur',$utilisateur->getImage());
        $file =new File('img/default.png');
        if(!is_null($utilisateur->getImage()))
            $file = new File($utilisateur->getImage());

        $utilisateur->setImage($file);
        $form = $this->get('form.factory')->create(
            new UtilisateurType(),$utilisateur
            );
        if($form->handleRequest($request)->isValid()){

            if(!is_null($utilisateur->getImage())){
                $file = new File($utilisateur->getImage());
            if($file != null){
                    $dossierImage = $utilisateur->getDossier().'/img';
                    $extension = $file->guessExtension();
                    if (!$extension) {
                        $extension = 'jpg';
                    }
                    $nomImage = $utilisateur->getCode().'-'.date('dmYHi').'.'.$extension;
                    if($file == 'img/default.png'){
                        copy('img/default.png', $dossierImage.'/'.$nomImage);
                    }else{
                        $file->move($dossierImage, $nomImage);
                    }
                    
                    $utilisateur->setImage($dossierImage.'/'.$nomImage);
                    
                    $utilisateur->getCompte()->setImage($utilisateur->getImage());
                }
            }else{
                $utilisateur->setImage($session->get('imageUtilisateur'));
            }
            //$utilisateur->setAdmin(false);
            //$em->persist($utilisateur);
            $em->flush();
            $session = new Session();
            $session->set('compte',$utilisateur);
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Mise à jour de données",
                        'description'=>"Mise à jour de son compte"
                        ));
           
            return $this->redirect($this->generateUrl('trc_admin_mon_compte'));
            //*/
        }
       
        $gu = $this->get('trc_core.gu');
        $monposte = $gu->getMonPoste($utilisateur);
        $mesposte = $gu->getMesPoste($utilisateur);
        return $this->render('TRCAdminBundle:Default:profil.html.twig',
            array(
                "moi"=>$utilisateur,
                "form"=>$form->createView(),
                'poste'=>$monposte,
                'postes'=>$mesposte
                ));
    }

    public function appsAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $service = null;
        $security = $this->get('security.context');
        if(!$security->isGranted('ROLE_ADMIN'))
            throw new \Exception("ACCES INTERDIT", 1);
        if(!is_null($id))
            $service = $em->getRepository('TRCCoreBundle:App')
                    ->find($id);

        if(is_null($service))
            $service = new App();
        $form = $this->get('form.factory')->create(
            new AppType(),$service
            );
        if($form->handleRequest($request)->isValid()){

            //$service->setCode($this->get('trc_core.gu')->codeService($service));
            if(is_null($id))
            $em->persist($service);
            $em->flush();
            
             $this->get('trc_core.gu')->track(array(
                        'user'=>$this->getUser(),
                        'action'=>"Ajout/modification d'application",
                        'description'=>"Application: <b>".$service->getNom()." </b>"
                        ));
            return $this->redirect($this->generateUrl('trc_admin_apps'));
        }
        
        $dql   = "SELECT a FROM TRCCoreBundle:App a order by a.at desc";
        $query = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            20/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:apps.html.twig',
            array(
                "form"=>$form->createView(),
                'pagination' => $pagination
                ));
    }
}
