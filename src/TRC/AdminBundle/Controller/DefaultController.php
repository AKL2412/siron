<?php

namespace TRC\AdminBundle\Controller;
use \TRC\CoreBundle\Entity\Agent;
use \TRC\CoreBundle\Form\AgentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\Tests\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TRC\CoreBundle\Entity\Entite;
use TRC\CoreBundle\Form\EntiteType;
use TRC\CoreBundle\Entity\Client;
use TRC\CoreBundle\Form\ClientType;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('TRCAdminBundle:Default:index.html.twig');
    }
    
    public function ajoutUtilisateurAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $gu = $this->get('trc_core.gu');

        $agent = new Agent();
        if (!is_null($id)) {
            $agent = $em->getRepository('TRCCoreBundle:Agent')
                            ->find($id);
            if(is_null($agent))
                throw new \Exception("Erreur de paramètre #MODIFEMPL", 1);
           
        }
        $form = $this->get('form.factory')->create(
            new AgentType(),$agent
            );
        if($form->handleRequest($request)->isValid()){
            if(is_null($id)){
                $agent = $gu->creerCompte($agent);
                $em->persist($agent);
            }
            $em->flush();
            
            return $this->redirect($this->generateUrl('trc_admin_utilisateurs_voir',array('id'=>$agent->getId())));
        }
        
        return $this->render('TRCAdminBundle:Default:admin.html.twig',
            array(
                "form"=>$form->createView()
                ));
    }
    public function utilisateursAction(Request $request){

    	$em = $this->get('doctrine')->getManager();
      
       
        $dql   = "SELECT a FROM TRCCoreBundle:Agent a";
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
       	$employe = $em->getRepository('TRCCoreBundle:Agent')
       					->find($id);

       	if(is_null($employe))
       		throw new \Exception("Erreur lors de la récupération des dinformations de l'utilisateur", 1);
        
       
      
        return $this->render('TRCAdminBundle:Default:employe.html.twig',
            array("agent"=>$employe
                ));
    }
    
    public function servicesAction(Request $request,$id = null){
        
        $em = $this->getDoctrine()->getManager();
        $service = null;
        if(!is_null($id))
            $service = $em->getRepository('TRCCoreBundle:Entite')
            ->find($id);
            
            if(is_null($service))
                $service = new Entite();
                $form = $this->get('form.factory')->create(
                    new EntiteType(),$service
                    );
                if($form->handleRequest($request)->isValid()){
                    
                    if(is_null($id))
                        $em->persist($service);
                        $em->flush();
                        return $this->redirect($this->generateUrl('trc_admin_services'));
                }
                
                $dql   = "SELECT a FROM TRCCoreBundle:Entite a ";
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
    
    public function clientsAction(Request $request,$id = null){

        $em = $this->getDoctrine()->getManager();
        $client = new Client();
        if(!is_null($id))
            $client = $em->getRepository('TRCCoreBundle:Client')
                    ->find($id);

        
            
        $form = $this->get('form.factory')->create(
            new ClientType(),$client
            );
        if($form->handleRequest($request)->isValid()){

            if(is_null($id))
            $em->persist($client);
            $em->flush();
            return $this->redirect($this->generateUrl('trc_admin_clients'));
        }
        
        $dql   = "SELECT a FROM TRCCoreBundle:Client a ";
        $query = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            20/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:clients.html.twig',
            array(
                "form"=>$form->createView(),
                'pagination' => $pagination
                ));
    }
    public function servicesVoirAction(Request $request,$code){
        $em = $this->getDoctrine()->getManager();
        $entite = $em->getRepository('TRCCoreBundle:Entite')
                    ->findOneByCode($code);
        if(is_null($entite))
            throw new \Exception("Erreur de code ".$code, 1);
        $sql = "SELECT DISTINCT p FROM TRCCoreBundle:Agent p JOIN p.entite s WHERE s = :service";
        $query = $em->createQuery($sql);
        $query->setParameter('service',$entite);
       // $societes = $query->getResult();
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            15/*limit per page*/
        );
        return $this->render('TRCAdminBundle:Default:servicesVoir.html.twig',
            array(
               // "form"=>$form->createView(),
                'entite' => $entite,
                'postes'=>$pagination
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
