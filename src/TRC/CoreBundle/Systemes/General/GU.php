<?php 

namespace  TRC\CoreBundle\Systemes\General;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Systemes\General\Core;
use TRC\CoreBundle\Entity\Tracking as Track;
use TRC\CoreBundle\Entity\Param;
use TRC\CoreBundle\Entity\Fichier;
use TRC\UserBundle\Entity\User as Compte;
use Symfony\Component\HttpFoundation\File\File;
use TRC\CoreBundle\Entity\Demat\DematFonction;
use TRC\CoreBundle\Entity\Demat\DematTrace;
class GU
{

	protected $em;
	protected $cheminPrincipal;
	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	   $this->cheminPrincipal = 'scte/';
	}

	public function classerActionsDematByScenario($actions){

		$datas = array();

		foreach ($actions as $key => $act) {
			$idScenario = $act->getScenario()->getId();
			if(!array_key_exists($idScenario, $datas)){
				$datas[$idScenario] = array(
						'scenario' => $act->getScenario(),
						'actions'=>0,
						'auteurs'=>array()
						);
			}
			$idPoste = $act->getPoste()->getId();
			if(!array_key_exists($idPoste, $datas[$idScenario]['auteurs'])){
				$datas[$idScenario]['auteurs'][$idPoste] = array(
						'auteur' => $act->getPoste(),
						'actions'=>array()
						);
			}
			$datas[$idScenario]['auteurs'][$idPoste]['actions'][] = $act;
			$datas[$idScenario]['actions'] += 1 ;
		}

		return $datas;
	}
	

	public function createUtilisateur(\TRC\CoreBundle\Entity\Utilisateur $utilisateur,$roles){
		$u = $this->em->getRepository('TRCCoreBundle:Utilisateur')
					->findOneByMatricule($utilisateur->getMatricule());
		if(!is_null($u))
			throw new \Exception("Matricule déjà enregistré :".$utilisateur->getMatricule(), 1);
			
		$code = $utilisateur->getMatricule();
		$dossier = "base/".$code;
		$utilisateur->setDossier($dossier);
		
		$dossierImage = $dossier.'/img';
		if(!file_exists($dossier))
			mkdir($dossier,0777);
		if(!file_exists($dossierImage))
			mkdir($dossierImage,0777);

		$utilisateur->setCode($code);
		
		$file = new File($utilisateur->getImage());
			if($file != null){
                    $extension = $file->guessExtension();
                    if (!$extension) {
                        $extension = 'jpg';
                    }
                    $nomImage = $utilisateur->getCode().'-'.date('dmYHis').'.'.$extension;
                    if($file == 'img/default.png'){
                    	if($utilisateur->getCivilite() == "Mr")
                    		copy('img/dh.png', $dossierImage.'/'.$nomImage);
                    	else
                    		copy('img/df.png', $dossierImage.'/'.$nomImage);

                    }else{
                    	$file->move($dossierImage, $nomImage);
                    }
                    
                    $utilisateur->setImage($dossierImage.'/'.$nomImage);
                }
        if(count($roles) > 0 ){
        	$utilisateur->setCompte($this->creerCompte($utilisateur,$roles));
        	$utilisateur->setParam(new Param());
        }
        return $utilisateur;
			
	}
	public function classerMessage(array $sms){
		$array = array();

		foreach ($sms as $key => $s) {
			$send = $s->getSend();
			if(!array_key_exists($send->getId(), $array)){
				$array[$send->getId()] = array(
					"s"=>$s,
					"t"=>1
					);
			}else{
				$array[$send->getId()]["t"] = $array[$send->getId()]["t"] + 1; 
			}
		}
		return $array;
	}
	public function estMaDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\UserBundle\Entity\User $compte){

		$moi = $this->getEmploye($compte);
		$moi = $this->getMonPoste($moi);
		if($moi == $demat->getAuteur())
			return true;
        return false;
			
	}

	public function estMonDocumentDemat(\TRC\CoreBundle\Entity\Demat\Document $document,\TRC\UserBundle\Entity\User $compte){
		$demat = $this->em->getRepository('TRCCoreBundle:Demat\Demat')
					->findOneByDocumentation(
						$document->getDocumentation()
						);
		if(!is_null($demat) && 
			!$demat->getStatuter()){
			
		$moi = $this->getEmploye($compte);
		$moi = $this->getMonPoste($moi);
		if($moi == $document->getProprietaire())
			return true;
		}
        return false;
			
	}

	public function estExecuteurDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\UserBundle\Entity\User $compte){

		$moi = $this->getEmploye($compte);
		$moi = $this->getMonPoste($moi);
		if($moi == $demat->getExecuteur())
			return true;
        return false;
			
	}

	public function responsableService(\TRC\CoreBundle\Entity\Service $service){

		$sql = "SELECT DISTINCT p FROM TRCCoreBundle:Poste p JOIN p.service s JOIN p.fonction f WHERE s =:service AND p.active = :active AND f.responsable = :active";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array(
				"service"=>$service,"active"=>true));
        $results = $query->getResult();

        if(count($results) > 0)
        	return $results[0];
        return null;
			
	}

	public function jeSuisResponsableService(\TRC\UserBundle\Entity\User $compte){

		$poste = $this->getMonPoste(
			$this->getEmploye($compte)
			);
		if(!is_null($poste) &&
			$poste->getFonction()->getResponsable()
			)
			return true;
	
        return false;
			
	}


	public function createReunion(\TRC\CoreBundle\Entity\Reunion $reunion){

		$comite = $reunion->getComite();
		if(is_null($comite))
			throw new \Exception("Pas de comite défini", 1);
		$code = $this->codeReunion($reunion);
		$reunion->setCode($code);
		$dossier = $comite->getDossier();
		if(!file_exists($dossier))
			mkdir($dossier,0777);
		$dossier = $dossier."/".$code;
		if(!file_exists($dossier))
			mkdir($dossier,0777);
		$reunion->setDossier($dossier);
        return $reunion;
			
	}
	public function createPoint(\TRC\CoreBundle\Entity\Point $point){

		$reunion = $point->getReunion();
		if(is_null($reunion))
			throw new \Exception("Pas de réunion définie", 1);
		$code = $this->codePoint($point);
		$point->setCode($code);
		$dossier = $reunion->getDossier();
		if(!file_exists($dossier))
			mkdir($dossier,0777);
		$dossier = $dossier."/".$code;
		if(!file_exists($dossier))
			mkdir($dossier,0777);
		$point->setDossier($dossier);
        return $point;
			
	}
	private function codeReunion(\TRC\CoreBundle\Entity\Reunion $reunion){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Reunion')
				->findByComite($reunion->getComite())
			)+1;
		$code =$reunion->getComite()->getCode().$reunion->getDatereunion()->format('dmY').Core::position($index);
		return $code;
	}

	public function createArticle(\TRC\CoreBundle\Entity\Actu\Article $article){
		
		if(is_null($article->getId())){
			$index = count($this->em->getRepository('TRCCoreBundle:Actu\Article')->findAll()) + 1;
			$slug = Core::slugify(Core::position($index)." ".$article->getTitre());
			$article->setSlug($slug);
		}else{
			$tab = explode("-", $article->getSlug());
			$index = $tab[0];
			$slug = Core::slugify($index." ".$article->getTitre());
			$article->setSlug($slug);
		}
		
		return $article;
	}

	public function creerDossierObjet(\TRC\CoreBundle\Entity\Objet\Objet $objet){
		
		$base = "demats/";
		if(!is_null($objet->getParent())){
			$base = $this->creerDossierObjet($objet->getParent());
			$base = $base.$objet->getCode()."/";
				if(!is_dir($base)){
					mkdir($base, 0777);
					$objet->setDossier($base);
				}
		}		
		else{
			$base = "demats/".$objet->getCode()."/";
				if(!is_dir($base)){
					mkdir($base, 0777);
					$objet->setDossier($base);
				}
		}
		$this->em->flush();
		return $base;
	}
	private function codeAvocat(){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:SNA\Avocat')
				->findAll()
			)+1;
		$code = Core::position($index).Core::generateRandomString(3);
		return $code;
	}
	private function codeDossier(){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:SNA\Dossier')
				->findAll()
			)+1;
		$code = Core::position($index).Core::generateRandomString(3).date('dmYHis');
		return $code;
	}
	public function createAvocat(\TRC\CoreBundle\Entity\SNA\Avocat $avocat){
		
		$base = "avocats/";
		$code = $this->codeAvocat();
		$dossier = $base.$code."/";
		$avocat->setCode($code);
		if(!is_dir($dossier)){
			mkdir($dossier, 0777);
		}
		$avocat->setDossier($dossier);
		return $avocat;
	}

	public function createDossier(\TRC\CoreBundle\Entity\SNA\Dossier $dos){
		
		$base = "dossiers-sna/";
		$code = $this->codeDossier();
		$dossier = $base.$code."/";
		$dos->setCode($code);
		if(!is_dir($dossier)){
			mkdir($dossier, 0777);
		}
		$dos->setDossier($dossier);
		return $dos;
	}

	public function createCampagne(\TRC\CoreBundle\Entity\API\Campagne $campagne){
		
		$base = "dossiers-campagne/";
		$code = $this->codeCampagne();
		$dossier = $base.$code."/";
		$campagne->setCode($code);
		if(!is_dir($dossier)){
			mkdir($dossier, 0777);
		}
		$campagne->setDossier($dossier);
		return $campagne;
	}

	private function codeCampagne(){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:API\Campagne')
				->findAll()
			)+1;
		$code = Core::position($index).Core::generateRandomString(3).date('dmYHis');
		return $code;
	}
	public function creerDossierInstance(\TRC\CoreBundle\Entity\Objet\Instance $instance){
		
		
		if(is_null($instance->getObjet()->getDossier()))
			$base = $this->creerDossierObjet($instance->getObjet());
		else
			$base = $instance->getObjet()->getDossier().$instance->getCode()."/";

		//die($base);
		if(!is_dir($base)){
				mkdir($base, 0777);
				$instance->setDossier($base);
		}

		
		$this->em->flush();
		return $base;
	}

	public function creerDossierDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat){
		
		if(is_null($demat->getInstance()->getDossier()))
			$base = $this->creerDossierInstance($demat->getInstance());
		else
			$base = $demat->getInstance()->getDossier().$demat->getCode();
		
		if(!is_dir($base)){
			mkdir($base, 0777);
			$demat->setDossier($base);
		}
		$this->em->flush();
		return true;
	}

	public function codeScenario(\TRC\CoreBundle\Entity\Objet\Objet $objet){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Core\Scenario')
				->findByObjet($objet)
			)+1;
		$code ="SCE".$objet->getCode().Core::position($index);
		return $code;
	}
	public function codeDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,$mat){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Demat\Demat')
				->findByInstance($demat->getInstance())
			)+1;
		$code =Core::position($index).date('dmy').$demat->getInstance()->getCode().$mat;

		return $code;
	}
	private function codePoint(\TRC\CoreBundle\Entity\Point $point){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Point')
				->findByReunion($point->getReunion())
			)+1;
		$code ="PT".Core::position($index);
		return $code;
	}
	public function monComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){

		if($comite->getActive() && $comite->getAuteur() == $this->getEmploye($user))
			return true;
		return false;
			
	}
	public function monPoint(\TRC\CoreBundle\Entity\Point $point,\TRC\UserBundle\Entity\User $user){

		if($point->getResponsable()->getEmploye() == $this->getEmploye($user))
			return true;
		return false;
			
	}
	public function tempsPoint(\TRC\CoreBundle\Entity\Point $point){

		/*
		if(!is_null($point->getDatedebut()))
			$today = $point->getDatedebut()->format('Y-m-d H:i:s');
		else
		//*/
		$today = (new \DateTime())->format('Y-m-d H:i:s');
		$at = $point->getAt()->format('Y-m-d H:i:s');
		$dealine = $point->getDeadline()->format('Y-m-d H:i:s');
        
        
        
        $total = strtotime($dealine) - strtotime($at);
        $restant = strtotime($dealine) - strtotime($today);


		return array('total'=>$total,
			'at'=>$at,'restant'=>$restant,
			'taux'=>round(intval($restant)/intval($total)*100,2),
			'jours'=>Core::nbreDeJours(new \DateTime(),$point->getDeadline())
			);// 
			
	}

	public function supprimerCommentaire(\TRC\CoreBundle\Entity\Commentaire $com,\TRC\UserBundle\Entity\User $user){

		if($com->getAuteur() == $user){
			$d = $com->getAt()->format('Y-m-d H:i:s');
        	$f = (new \DateTime())->format('Y-m-d H:i:s');
            $times = strtotime($f) - strtotime($d);
            if($times < 300)
				return true;
		}
		return false;
			
	}
	
	public function secretariatReunion(\TRC\CoreBundle\Entity\Reunion $reunion,\TRC\UserBundle\Entity\User $user){
		$comite = $reunion->getComite();
		if(!is_null($comite) && $reunion->getActive()){
		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Membre m JOIN m.comite c JOIN m.statut s WHERE c = :comite AND s.code = :code ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite,"code"=>"SG"));
		foreach ($query->getResult() as $key => $membre) {

			if($membre->getEmploye()->getCompte() == $user)
				return true;
		}
	}
		return false;
			
	}
	public function secretariatComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){

		if($comite->getActive()){
		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Membre m JOIN m.comite c JOIN m.statut s WHERE c = :comite AND s.code = :code ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite,"code"=>"SG"));
		foreach ($query->getResult() as $key => $membre) {

			if($membre->getEmploye()->getCompte() == $user)
				return true;
		}
	}
		return false;
			
	}
	public function presidenceComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){

		if($comite->getActive()){
		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Membre m JOIN m.comite c JOIN m.statut s WHERE c = :comite AND s.code = :code ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite,"code"=>"PDT"));
		foreach ($query->getResult() as $key => $membre) {

			if($membre->getEmploye()->getCompte() == $user)
				return true;
		}
	}
		return false;
			
	}

	public function chiffreComite(\TRC\CoreBundle\Entity\Comite $comite){

		$chiffres = array();
		$chiffres['membres'] = count(
			$this->em->getRepository('TRCCoreBundle:Membre')
				->findByComite($comite)
			);
		$chiffres['réunions'] = count(
			$this->em->getRepository('TRCCoreBundle:Reunion')
				->findByComite($comite)
			);
		
		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Point m JOIN m.reunion r JOIN r.comite c WHERE c = :comite  ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite));
		$chiffres['actions'] = count($query->getResult());
		return $chiffres;
			
	}

	public function chiffreReunion(\TRC\CoreBundle\Entity\Reunion $reunion){

		$chiffres = array();
		$dql   = "SELECT a FROM TRCCoreBundle:Participant a JOIN a.reunion r WHERE r = :reunion AND a.active = :active ";
        $query = $this->em->createQuery($dql);
        $query->setParameters(array('reunion'=>$reunion,'active'=>true));
           
		$chiffres['participants'] = count($query->getResult());
		$dql   = "SELECT a FROM TRCCoreBundle:Point a JOIN a.reunion r WHERE r = :reunion ORDER BY a.deadline ASC";
        $query = $this->em->createQuery($dql);
        $query->setParameter('reunion',$reunion);
          
		$chiffres['actions'] = count($query->getResult());
		return $chiffres;
			
	}

	public function getMembreSecretariatComite(\TRC\CoreBundle\Entity\Comite $comite){


		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Membre m JOIN m.comite c JOIN m.statut s WHERE c = :comite AND s.code = :code ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite,"code"=>"SG"));
		
		return $query->getResult();
			
	}
	public function getMembrePresidenceComite(\TRC\CoreBundle\Entity\Comite $comite){


		$sql = "SELECT DISTINCT m FROM TRCCoreBundle:Membre m JOIN m.comite c JOIN m.statut s WHERE c = :comite AND s.code = :code ";
		$query = $this->em->createQuery($sql);
		$query->setParameters(
			array("comite"=>$comite,"code"=>"PDT"));
		
		return $query->getResult();
			
	}
	public function getFonctionSecretariatComite(\TRC\CoreBundle\Entity\Comite $comite){

		$fonctions = array();
		$membres = $this->getMembreSecretariatComite($comite);
		foreach ($membres as $key => $value) {
			$poste = $this->getMonPoste($value->getEmploye());
			if(!is_null($poste) && !in_array($poste->getFonction(), $fonctions))
				$fonctions[] = $poste->getFonction();
		}
		return $fonctions;
			
	}


	public function jaimeComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){

		$aimer = $this->em->getRepository('TRCCoreBundle:Aimer')
				->findOneBy(
					array(
							'comite'=>$comite,
							'auteur'=>$user
						),
					array(),null,0);
		if(!is_null($aimer))
			return true;
		return false;
			
	}
	public function jaimeReunion(\TRC\CoreBundle\Entity\Reunion $comite,\TRC\UserBundle\Entity\User $user){

		$aimer = $this->em->getRepository('TRCCoreBundle:Aimer')
				->findOneBy(
					array(
							'reunion'=>$comite,
							'auteur'=>$user
						),
					array(),null,0);
		if(!is_null($aimer))
			return true;
		return false;
			
	}
	public function jaimePoint(\TRC\CoreBundle\Entity\Point $comite,\TRC\UserBundle\Entity\User $user){

		$aimer = $this->em->getRepository('TRCCoreBundle:Aimer')
				->findOneBy(
					array(
							'point'=>$comite,
							'auteur'=>$user
						),
					array(),null,0);
		if(!is_null($aimer))
			return true;
		return false;
			
	}

	private function codeUtilisateur(\TRC\CoreBundle\Entity\Utilisateur $utilisateur){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Utilisateur')
				->findALl()
			)+1;
		$code = Core::position($index).Core::generateRandomString(3);
		return $code;
	}
	private function codeComite(\TRC\CoreBundle\Entity\Comite $utilisateur){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Comite')
				->findBySociete($utilisateur->getSociete())
			)+1;
		$code = Core::position($index).Core::generateRandomString(3);
		return $code;
	}

	public function codeService(\TRC\CoreBundle\Entity\Service $service){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Service')
				->findBySociete($service->getSociete())
			)+1;
		$code = Core::position($index).Core::generateRandomString(3);
		return $code;
	}
	public function codePoste(\TRC\CoreBundle\Entity\Poste $poste){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Poste')
				->findByService($poste->getService())
			)+1;
		$code = Core::position($index).$poste->getService()->getCode();
		return $code;
	}

	public function codeEvolution(\TRC\CoreBundle\Entity\Evolution $poste){
		$index = count(
			$this->em->getRepository('TRCCoreBundle:Evolution')
				->findByPoint($poste->getPoint())
			)+1;
		$code = Core::position($index).Core::generateRandomString(8);
		return $code;
	}

	public function getMonPoste(\TRC\CoreBundle\Entity\Utilisateur $poste){
		return $this->em->getRepository('TRCCoreBundle:Poste')
				->findOneBy(
					array('employe'=>$poste,'active'=>true),
					array(),null,0);
			
	}

	public function getObjetsAvecScenariosAdmin(){
		$objets = $this->em->getRepository('TRCCoreBundle:Objet\Objet')
				->findByActive(true);
		$array =array();

		foreach ($objets as $key => $objet) {
			$scs = $this->em->getRepository('TRCCoreBundle:Core\Scenario')
					->findBy(
						array("objet"=>$objet,"active"=>true),
						array(),null,0);
			if( count($scs) > 0)
				$array[] = $objet;
		}
		return $array;	
	}
	public function getObjetsAvecScenarios(\TRC\CoreBundle\Entity\Fonction $fonction){
		$objets = $this->em->getRepository('TRCCoreBundle:Objet\Objet')
				->findByActive(true);
		$array =array();

		foreach ($objets as $key => $objet) {
			if($this->objetCouvreFonction($objet,$fonction))
				$array[] = $objet;
		}
		return $array;	
	}

	private function objetCouvreFonction(\TRC\CoreBundle\Entity\Objet\Objet $objet, \TRC\CoreBundle\Entity\Fonction $fonction){

		$sql = "SELECT DISTINCT d FROM TRCCoreBundle:Core\Scenario d JOIN d.objet o WHERE o = :objet and d.active = true";
		$query = $this->em->createQuery($sql);
		$query->setParameters(array(
			'objet'=>$objet
			));
		
		$scenarios = $query->getResult();
		foreach ($scenarios as $key => $sce) {
			if($sce->couvreFonction($fonction))
				return true;
		}
		return false;
	}

	public function pointToArray(\TRC\CoreBundle\Entity\Point $point){
		$array = Core::pointToArray($point);
		$poste = $this->getMonPoste($point->getResponsable()->getEmploye());
		if(!is_null($poste))
			$array['responsable'] = $poste->getFonction()->getCode();
		return $array;
			
	}
	public function getMesPoste(\TRC\CoreBundle\Entity\Utilisateur $poste){
		return $this->em->getRepository('TRCCoreBundle:Poste')
				->findBy(
					array('employe'=>$poste),
					array('at'=>'desc'),null,0);
			
	}

	public function getEmploye(\TRC\UserBundle\Entity\User $compte){
		return $this->em->getRepository('TRCCoreBundle:Utilisateur')
			->findOneByCompte($compte);
	}
	public function creerCompte(\TRC\CoreBundle\Entity\Agent $agent){
		
		
		$motdepasse = "a123*123";
		
			$compte = new Compte();
			$compte->setEmail($agent->getEmail());
			$compte->setUsername(explode("@", $agent->getEmail())[0]);
			$compte->setPlainPassword($motdepasse);
			$compte->setEnabled(true);
			$compte->setRoles(array("ROLE_".$agent->getProfil()->getCode()));
			//$this->em->persist($compte);
			//$this->em->flush();
			$agent->setCompte($compte);
			return $agent;
		
		
	}

	public function track(array $datas){
		$track = new Track();
		if(array_key_exists("user", $datas) && !is_null($datas['user'])){

			$user = $datas['user'];
			$utilisateur = $this->em->getRepository('TRCCoreBundle:Utilisateur')
					->findOneByCompte($user);
			if(!is_null($utilisateur)){
				$track->setNom($utilisateur->getPrenom()." ".strtoupper($utilisateur->getNom()));
			}
			$track->setUser($user->getUsername());
			$track->setRoles(implode(" ",$user->getRoles()));
		}elseif(array_key_exists("login", $datas)){
			$track->setUser($datas['login']);
			$track->setNom($datas['login']);
		}
		
		if(array_key_exists("action", $datas)){
			$track->setAction($datas['action']);
		}	
		$track->setDescription($datas['description']);
		if(array_key_exists("archive", $datas)){
			$track->setArchive(true);
		}

		if(array_key_exists("app", $datas)){
			$track->setApp($datas['app']);
		}
		$this->em->persist($track);
		$this->em->flush();
		return true;
	}

	public function dematTrace(array $datas){
		$track = new DematTrace();

		$user = $datas['user'];
		$utilisateur = $this->em->getRepository('TRCCoreBundle:Utilisateur')
					->findOneByCompte($user);
		if(!is_null($utilisateur)){
				$track->setNom($utilisateur->getPrenom()." ".strtoupper($utilisateur->getNom()));
		}
		$track->setUser($user->getUsername());
		$track->setAction($datas['action']);
			
		$track->setDescription($datas['description']);
		$track->setDemat($datas['demat']);
		if(array_key_exists("archive", $datas)){
			$track->setArchive(true);
		}

		if(array_key_exists("icon", $datas)){
			$track->setIcon($datas['icon']);
		}
		$this->em->persist($track);
		$this->em->flush();
		return true;
	}

	public function getIndisponibile(\TRC\CoreBundle\Entity\Utilisateur $employe){

        return $this->em->getRepository('TRCCoreBundle:Indisponible')
                	->findOneBy(
                		array(
                			'active'=>true,
                			'employe'=>$employe
                			),
                		array(),null,0);

	}

	public function toStringScenario(\TRC\CoreBundle\Entity\Core\Scenario $scenario){
		$sql = "SELECT c FROM TRCCoreBundle:Core\Condition c JOIN c.scenario s JOIN c.paramtre p WHERE c.active = :active AND s = :scenario";
                $query = $this->em->createQuery($sql);
                $query->setParameters(
                    array(
                        'scenario'=>$scenario,
                        'active'=>true,
                        ));
        $conditions =  $query->getResult();
        $str = "Avant d'envoyer une démat (".$scenario->getObjet()->getNom().") concernant : <ul>";
        foreach ($scenario->getInstances() as $key => $value) {
        	$str .= '<li>'.$value->getNom().'</li>';
        }
        $str .= "</ul> aux fonctions suivantes : <ul>";
        foreach ($scenario->getExecuteurs() as $key => $value) {
        	$str .= '<li>'.$value->getNom().'</li>';
        }
        $str .= "</ul> la démat doit respecter les conditions suivantes : <ol>";
      
        foreach ($conditions as $key => $value) {
        	$str .= '<li>'.$this->evaluerExpression($value,$value->getExpression(),'non').'</li>';
        }
        $str .= '</ol><hr>';
        return $str;

	}
	public function getNextScenario(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\CoreBundle\Entity\Poste $nextPoste){
		$instance = $demat->getInstance();
		$scenario = $demat->getScenario();
		$paramatres = $this->getParametresDemat($demat);
		$data = array();
		
			if(!$this->verifyScenario($scenario,$paramatres))
				throw new \Exception("Désolé votre démat ne respecte pas les conditions initiales ".$this->toStringScenario($scenario), 1);
				
	$ssce =  $this->getScenarioByObjetAndExecuteurs($instance,$nextPoste);
		//*
	$str = "";
		foreach ($ssce as $key => $scene) {
			
			if($this->verifyScenario($scene,$paramatres))
				$data[] = $scene;
			else
				$str .= "<h6>".$scene->getNom()."</h6>".
						$this->toStringScenario($scene);
		}

		if(count($data) == 0 && strlen($str) > 2 )
			throw new \Exception($str, 1);
			

		return $data;
		//*/
			
	}

	public function getScenarioByObjetAndExecuteurs(\TRC\CoreBundle\Entity\Objet\Instance $instance,\TRC\CoreBundle\Entity\Poste $nextPoste){

		$sql = "SELECT DISTINCT s FROM TRCCoreBundle:Core\Scenario s JOIN s.executeurs e JOIN s.objet o JOIN s.instances i WHERE s.active = :active AND o = :objet AND e = :executeur AND i = :instance";
		$query = $this->em->createQuery($sql);
		$query->setParameters(array(
			'active'=>true,
			'objet'=>$instance->getObjet(),
			'instance'=>$instance,
			'executeur'=>$nextPoste->getFonction()

			));
		return $query->getResult();
			
	}
	public function verifyScenario(\TRC\CoreBundle\Entity\Core\Scenario $scenario, $parametres){

		
		foreach ($parametres as $key => $paramValeur) {
			$conditions = $this->getConditionByScenarioParametreValidation($scenario,$paramValeur->getParametre());
			
			if(
				count($conditions) > 0 && 
				!$this->verificationConditionValidation($conditions, $paramValeur->getValeur())
				){
				
				return false;

			}
				
		}

		 return true;
	}

	public function getMessageErreurSendDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat){
		$scenario = $demat->getScenario();
		$service = $demat->getService();
		$entite = $scenario->getEntite();
		$message = "";

		if($entite == 'même'){

		}elseif ($entite == 'parente') {

		}else{

		}
		return $message;
	}
	public function getNextExecuteur(\TRC\CoreBundle\Entity\Demat\Demat $demat,$nextExecuteurs){
		if(count($nextExecuteurs) == 1 )
			return $nextExecuteurs[0];
		$actions = $this->em->getRepository('TRCCoreBundle:Demat\DematAction')
					->findByDemat($demat);
		foreach ($actions as $key => $action) {
			
			foreach ($nextExecuteurs as $k => $poste) {
				if($action->getPoste() == $poste)
					return $poste;
			}
		}

		return null;
	}
	public function getNextExecuteurs(\TRC\CoreBundle\Entity\Demat\Demat $demat){
		
		$scenario = $demat->getScenario();
		$service = $demat->getService();

		$entite = $scenario->getEntite();
		$criteres = array();
		if($entite == 'même'){
			$sql = "SELECT DISTINCT p FROM TRCCoreBundle:Poste p JOIN p.service s JOIN p.fonction f WHERE s = :service";
			$criteres['service'] = $service;
		}elseif ($entite == 'parente') {
			if(is_null($service->getParent()))
				throw new \Exception($service->getNom()." n'a pas d'entité parente or le scénario paramétré exige à ce que la démat soit transferée à cette dernière. Veuillez contacter l'administrateur pour cet incident. #VDD-GU-NEX", 1);
			$sql = "SELECT DISTINCT p FROM TRCCoreBundle:Poste p JOIN p.service s JOIN p.fonction f WHERE s = :service";
			$criteres['service'] = $service->getParent();
				
		}else{
			$sql = "SELECT DISTINCT p FROM TRCCoreBundle:Poste p JOIN p.service s JOIN p.fonction f WHERE p.id is not null ";
		}
		$sql .=" AND p.active = :active AND f in (:fonctions) ";
		$criteres['active'] = true;
		$criteres['fonctions'] = $scenario->getReceptionnaires();
		$query = $this->em->createQuery($sql);
		$query->setParameters($criteres);
		$postes = $query->getResult();
		$services =array();
		$disponibilite = false;
		$personnes = "<ul>";
		foreach ($postes as $key => $p) {
			if(!array_key_exists($p->getService()->getId(), $services))
				$services[$p->getService()->getId()] = $p->getService();
			if($p->getEmploye()->getParam()->getDisponible())
				$disponibilite = true;
			$personnes .= '<li><b>'.$p->getEmploye()->nomprenom().'</b> <em>'.
						$p->getService()->getNom().'</em></li>';
		}
		$personnes .= "</ul>";
		return array('p'=>$postes,'s'=>$services,'d'=>$disponibilite,'message'=>$personnes);
	}
	public function getConditionByScenarioParametreValidation(\TRC\CoreBundle\Entity\Core\Scenario $scenario,\TRC\CoreBundle\Entity\Objet\Parametre $parametre ){

		 $sql = "SELECT c FROM TRCCoreBundle:Core\Condition c JOIN c.scenario s JOIN c.paramtre p WHERE c.active = :active AND s = :scenario AND p =:parametre";
                $query = $this->em->createQuery($sql);
                $query->setParameters(
                    array(
                        'scenario'=>$scenario,
                        'active'=>true,
                        'parametre'=>$parametre
                        ));
        return $query->getResult();
        
	}
	public function verificationConditionValidation(array $conditions,$valeur){

		foreach ($conditions as $key => $cond) {
			$expression = $cond->getExpression();
			if(!$this->evaluerExpression($valeur,$expression))
				return false;
		}
		return true;
	}

	public function fichierExcelImportVerif($entete){

		$params = array(
		    'A' => "AGENCELIB",
		    'B' => "CLIENT",
		    'C' => "COMPTE",
		    'D' => "NOM",
		    'E' => "DATOPER",
		    'F' => "DATVAL",
		    'G' => "MNTDEV",
		    'H' => "LIBELLE",
		    'I' => "NOOPER"
		);

		if(count($params) != count($entete))
			return array(
				'code' => -1,
				'message'=>"Le nombre de colonne du fichier ne correspond pas"
				);
		foreach ($params as $key => $value) {
			if(!array_key_exists($key, $entete) || $value != $entete[$key] )
				return array(
				'code' => -1,
				'message'=>"La première ligne du fichier doit comporter exactement ces colonnes ".$this->arrayToString($params)." Vous avez chargé ceci : ".$this->arrayToString($entete)."<h4> colonne : ".$key."</h4>"
				);
		}

		return array(
				'code' => 1,
				'message'=>"Entête correct"
				);
	}

	public function fichierExcelSMS($entete){

		$params = array(
		    'A' => "NUMERO",
		    'B' => "MESSAGE"
		);

		if(count($params) != count($entete))
			return array(
				'code' => -1,
				'message'=>"Le nombre de colonne du fichier ne correspond pas"
				);
		foreach ($params as $key => $value) {
			if(!array_key_exists($key, $entete) || $value != $entete[$key] )
				return array(
				'code' => -1,
				'message'=>"La première ligne du fichier doit comporter exactement ces colonnes ".$this->arrayToString($params)." Vous avez chargé ceci : ".$this->arrayToString($entete)."<h4> colonne : ".$key."</h4>"
				);
		}

		return array(
				'code' => 1,
				'message'=>"Entête correct"
				);
	}
	private function arrayToString($array){
		$str = "<ol>";
		foreach ($array as $key => $value) {
			$str .= '<li>'.$key.' => '.$value.'</li>';
		}
		$str .= "</ol>";
		return $str;
	}

	public function numFlotte($num){
		
    	$num = str_replace(" ", "", $num);
    	$pos = strpos($num, "70638");
    	if($pos)
    		return true;
    	return false;
	}

	public function traiterNum($from,$flotte = null){
		
    	$from = str_replace(" ", "", $from);
		$from = str_replace("(", "", $from);
		$from = str_replace(")", "", $from);
		if(!is_null($flotte))
			$from = str_replace("+221", "", $from);
		return $from;
	}
	public function sendSMS($from,$to,$message){

		$from = $this->traiterNum($from);
		$to = $this->traiterNum($to);

		$data = array(
		    "sms_to" => $to,
		    "sms_text" => $this->encodeToIso($message)
		);
		
		$datas = array();
		try {
			$url = "http://41.219.0.108:7721/selfcare/api_sms_ba.php/envoisms/$from";
			$response = CallAPI("POST", $url, $data);
			$result = json_decode($response);
			if(!is_null($response)){
				//$datas = $response['result'];
				$datas = array(
						'code' => 200,
						'status' => $response->result->status,
						'data' => $response->result->data
					);
			}else{
				$datas = array(
						'code' => 333,
						'status' => 'ERREUR',
						'data' => "RESPONSE NULL"
					);
			}
		} catch (\Exception $e) {
			$datas = array(
						'code' => 303,
						'status' => 'ERREUR CATCH',
						'data' => $e->getMessage()
					);
		}
		return $datas;
	}
	public function encodeToIso($string){

	return mb_convert_encoding($string, "ISO-8859-1",mb_detect_encoding($string,"UTF-8, ISO-8859-1,ISO-8859-15",true));
	}
	private function CallAPI($method, $url, $data = false) {
	    $curl = curl_init();

	    switch ($method) {
	        case "POST" :
	            curl_setopt($curl, CURLOPT_POST, 1);

	            if ($data)
	                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	            break;
	        case "PUT" :
	            curl_setopt($curl, CURLOPT_PUT, 1);
	            break;
	        default :
	            if ($data)
	                $url = sprintf("%s?%s", $url, http_build_query($data));
	    }
	    // Optional Authentication:
	    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    curl_setopt($curl, CURLOPT_USERPWD, "username:password");
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    $result = curl_exec($curl);
	    curl_close($curl);
	    return $result;
	}
}