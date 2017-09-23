<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Entity\DDC\Fichier;
use TRC\CoreBundle\Entity\Client\Client;
use TRC\CoreBundle\Systemes\General\Core;

class ImportController extends Controller
{
    public function indexAction()
    {
        return $this->render('TRCAdminBundle:Default:index.html.twig');
    }
    public function chargementAction(Request $request){

    	$dossiers = "fichiers-importes/";
    	$datas = array();
    	$fichier = false;
        $em = $this->get('doctrine')->getManager();
        $user = $this->getUser();
    	if($request->isMethod('POST') && array_key_exists("fichier", $_FILES)){

    		
    		$fichier = true;
    		$f = new Fichier();
            $f->setUser($user);
    		$f->setNomoriginal($_FILES['fichier']['name']);
    		$f->setNom($_FILES['fichier']['name']);
    		$f->setType($_FILES['fichier']['type']);
    		$f->setUpload(true);
    		$rs = $user->getUsername()."-".$f->getDateajout()->format('YmdHis').".".Core::extension($f->getNomoriginal());
    		$chemin = $dossiers.$rs;
    		$f->setChemin($chemin);
    		$f->setRs($rs);

    		if(move_uploaded_file($_FILES['fichier']['tmp_name'], $f->getChemin())){

    			$em->persist($f);
    			$em->flush();

    			return $this->redirect(
    				$this->generateUrl('trc_admin_centre_chargement_fichier_presentation',array('id'=>$f->getId())));
    		}else{
    			throw new \Exception("Error Processing Request : Le fichier n'a pas été sauvegardé", 1);
    			
    		}
    		
    	}

        $datas = $em->getRepository('TRCCoreBundle:DDC\Fichier')
                ->findBy(
                    array('user'=>$user),
                    array('dateajout'=>"desc"),
                    null,0);
    	return $this->render('TRCAdminBundle:Default:chargement.html.twig',
    		array(
    			"datas"=>$datas,
    			"fichier"=>$fichier
    			));
    }

    public function extrationAction(Request $request,$id){

    	$em = $this->get('doctrine')->getManager();

    	$fichier = $em->getRepository('TRCCoreBundle:DDC\Fichier')
    				->findOneBy(
    					array(
    						"id"=>$id,
    						"upload"=>true),array(),null,0);
    	if(is_null($fichier) || !$fichier->ok())
    		throw new \Exception("Erreur de fichier :::".$id, 1);
    	
    	$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject($fichier->getChemin());
    	$sheetData = $phpExcelObject->getActiveSheet()->toArray(null,true,true,true);
    	$donnees= array();
    	$header = $this->header($sheetData);
    	$index = 0;
    	foreach ($sheetData as $key => $lf) {
                        $nlf = array();
                        foreach ($lf as $k => $v) {
                           if(count($nlf) < count($header))
                            $nlf[$k] = $v;
                        	else
                        	break;
                        }
                        if($index > 0)
                        	$donnees[$key] = $nlf;
                        $index +=1;
           }
        $cli = new Client();
        //$vars = get_class_methods("\TRC\CoreBundle\Entity\Client\Client");
        $identite = array(
            array("c"=>"nom","l"=>"nom","r"=>true),
            array("c"=>"ldn","l"=>"Lieu de naissance","r"=>false),
            array("c"=>"prenom","l"=>"prenom","r"=>true),
            array("c"=>"piece","l"=>"pièce","r"=>true),
            array("c"=>"numeroPiece","l"=>"Numéro de pièce","r"=>true),
            array("c"=>"ddn","l"=>"Date de naissance","r"=>false),
            array("c"=>"sdf","l"=>"Situation et Régime Matrimoniale","r"=>false),
            array("c"=>"pays","l"=>"Le pays","r"=>false),
            array("c"=>"civilite","l"=>"Civilité","r"=>true),

            );
        $coordonnees = array(
            array("c"=>"adresse","l"=>"Adresse","r"=>false),
            array("c"=>"boitePostale","l"=>"Boite Postale","r"=>false),
            array("c"=>"ville","l"=>"Ville","r"=>false),
            array("c"=>"telephoneProfessionnel","l"=>"Téléphone professionnel","r"=>true),
            array("c"=>"telephoneDomicile","l"=>"Téléphone domicile","r"=>false),
            array("c"=>"gsm","l"=>"GSM","r"=>true),
            array("c"=>"email","l"=>"Email","r"=>true),
            //array("c"=>"adresse","l"=>"Adresse","r"=>false),

            );
        $employeur = array(
            array("c"=>"denomination","l"=>"Dénomination de l'employeur","r"=>true),
            array("c"=>"secteur","l"=>"Secteur d'activité de l'employeur","r"=>true),
            array("c"=>"adresseSociale","l"=>"Adresse sociale de l'employeur","r"=>false),
            array("c"=>"telephone","l"=>"Téléphone de l'employeur","r"=>true),
            array("c"=>"fax","l"=>"Fax","r"=>false),
            array("c"=>"radical","l"=>"Racine de l'employeur","r"=>false),
            array("c"=>"ville","l"=>"Ville de l'employeur","r"=>false),
            //array("c"=>"adresse","l"=>"Adresse","r"=>false),

            );
        $profession = array(
            array("c"=>"fonction","l"=>"Métier du client","r"=>true),
            );
        $direct = array(
            array("c"=>"radical","l"=>"Racine du client","r"=>true),
            array("c"=>"agence","l"=>"Agence du client","r"=>true),
            array("c"=>"statut","l"=>"Statut du client","r"=>false),
            array("c"=>"gestionnaire","l"=>"Gestionnaire du client","r"=>false),
        );
        $clients = array(
            "direct"=>$direct,
            "identite"=>$identite,
            "coordonnee"=>$coordonnees,
            "employeur"=>$employeur,
            "profession"=>$profession
            );

        //$clients = get_class_methods(get_class($cli));
        $knp = $this->get('knp_snappy.pdf');
        /*
        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView(
                'TRCAdminBundle:Default:extration.html.twig',
                array(
                //'excel'=>$phpExcelObject,
                'datas'=>$donnees,
                'header'=>$header,
                'fichier'=>$fichier,
                "base"=>json_encode($clients)
                //'vars'=>$vars
                )
            ),
            'generates/file.pdf'
        );
        //*/
    	return $this->render('TRCAdminBundle:Default:extration.html.twig',
    		array(
    			//'excel'=>$phpExcelObject,
    			'datas'=>$donnees,
    			'header'=>$header,
    			'fichier'=>$fichier,
                //"base"=>json_encode($clients)
                //'vars'=>$vars
    			));
    }
    private function header($sheetData){

    	$header =array();
    	$index = 0;
    	foreach ($sheetData as $key => $lf) {
                        $nlf = array();
                        foreach ($lf as $k => $v) {
                           if(trim($v) != '')
                            $nlf[$k] = $v;
                        }
                        $header = $nlf;
                        $index += 1;
                        if($index > 0 )
                        	return $header;
           }

           return $header;
    }
}