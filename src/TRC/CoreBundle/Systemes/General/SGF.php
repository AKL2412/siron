<?php 

namespace  TRC\CoreBundle\Systemes\General;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Entity\Journal as JournalEntity;
use Symfony\Component\HttpFoundation\File\File;
use TRC\UserBundle\Entity\User as Compte;
use TRC\CoreBundle\Entity\Acteur as Actor;

use \TRC\CoreBundle\Entity\DDC\DDC;
use \TRC\CoreBundle\Entity\DDC\Fichier;

class SGF
{

	protected $em;
	protected $cheminPrincipal;
	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	   $this->cheminPrincipal = 'Utilisateurs/';
	}

	public function removeFichierDDC(\TRC\CoreBundle\ENtity\DDC\Fichier $fichier = null){

		try {
			if(!is_null($fichier)){
				if(is_file($fichier->getChemin())){

					if(unlink($fichier->getChemin()))
					return array("code"=>1,
						"message"=>"Le fichier [".$fichier->getNom()."] a été supprimé avec succès");
				}else{
						return array("code"=>1,
							"message"=>"Le fichier [".$fichier->getNom()."] n'a pas été supprimé car il n'existe pas physiquement");
				}
			}else{
				return array("code"=>1,
						"message"=>"Aucun fichier  n'a pas été supprimé ");
			}
			
		} catch (\Exception $e) {
			return array("code"=>0,
					"message"=>$e->getMessage());	
		}
	}
	public function uploadFichierDDC(\TRC\CoreBundle\ENtity\DDC\DDC $ddc,$type,$fichier,$nameuser = null){

		try {
		$message = "Rien pour le moment";
		$code = 1;
		$chemin = $ddc->getDossier();
		$no = $fichier['name'];
		$ext="";
		if(strlen($this->extension($no)) > 0)
			$ext = ".".$this->extension($no);
		$f = new Fichier();
		$f->setType($fichier['type']);
		$f->setNom($nameuser);
		if(is_null($nameuser)){
			$f->setNom($this->nomSansExtension($no));
		}
		$f->setRs($type.'-'.$ddc->getRs().'-'.date('dmYHis').$ext);
		
		$f->setNomoriginal($no);
		$doss = $chemin;
		if($type == "edp"){
			$f->setChemin($chemin."epd/".$f->getRs());
			$doss .= 'epd/';
		}else{
			$f->setChemin($chemin."fichiers/".$f->getRs());
			$doss .= 'fichiers/';
		}
			if(!is_dir($doss))
				mkdir($doss, 0777);
				
			if(is_dir($doss) && is_writable($doss)){

				if(move_uploaded_file($fichier['tmp_name'], $f->getChemin())){
					$message = "ok";
				}else{
					$message = "Le fichier n'a pas pu été sauvegardé (La taille!)";
				}
			}else{
				$message = "Le dossier de destination n'existe pas ou est protegé";
			}
			
			
			
		} catch (\Exception $e) {
				$message = "Erreur de téléchargement du fichier :: ".$no." // ".$e->getMessage();
			    $code = 0;
		}
		return  array("fichier"=>$f,
			"message"=>$message,
			"code"=>$code);
	}
	
	public function fonction(\TRC\CoreBundle\Entity\Utilisateur $utilisateur){

		return $this->em->getRepository('TRCCoreBundle:Fonction')
                    ->findOneBy(
                        array('acteur'=>$utilisateur->getActeur(),
                            'active'=>true,
                            'archive'=>false),
                        array('dateaffectation'=>'DESC'),
                        null,
                        0);
	}

	private function extension($nom){
		$tab = explode(".", $nom);
		if(count($tab)>1)
			return $tab[count($tab)-1];
		return "";
	}
	private function nomSansExtension($nom){
		$tab = explode(".", $nom);
		$nom = "";
		for ($i=0; $i < count($tab)-1; $i++) { 
			$nom .= $tab[$i];
		}
		return $nom;
	}
	public static function position($index){

		$chaine ="";
		if($index < 10)
			$chaine = "000".$index;
		elseif ($index < 100) 
			$chaine = "00".$index;
		elseif ($index < 1000) 
			$chaine = "0".$index;
		else
			$chaine = $index;
		return $chaine;

	}
	private function creerCompte($utilisateur){
		$compte = new Compte();
		$compte->setEmail($utilisateur->getEmail());
		$compte->setUsername('u'.$utilisateur->getMatricule());
		$compte->setPlainPassword('a123*123');
		$compte->setEnabled(true);
		$this->em->persist($compte);
		$this->em->flush();
		return $compte;
	}
	private function creerActor($utilisateur){
		$entite = new Actor();
        $entite->setClasse(get_class($utilisateur));
        $this->em->persist($entite);
		$this->em->flush();
        return $entite;
	}
	public function lesFichiers($dir){
		$nbre = 0;
		$lesFichiers = array();
		
		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh))) {
			if(is_dir($dir.$filename) && strlen($filename) > 3)
		    $lesFichiers[$filename] = $this->lesFichiers($dir.$filename.'/');
			else
				$lesFichiers[] = $filename;
		}

		return $lesFichiers;
	}
	private function fichierExist($chemincomplet,$dossier = false){

		if($dossier && is_dir($chemincomplet))
			return true;
		elseif (!$dossier && is_file($chemincomplet)) {
			return true;
		}
		return false;
	}
	private function enregistrer($array){

		$j = new JournalEntity();
		$j->setUser($array['user']);
		$j->setType($array['type']);
		$j->setContenu($array['contenu']);

		$this->em->persist($j);
		$this->em->flush();
		return true;
	}
}