<?php 
namespace  TRC\CoreBundle\Systemes\Twig;

use Doctrine\Common\Persistence\ObjectManager;
//use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use TRC\CoreBundle\Systemes\General\GU;
use TRC\CoreBundle\Systemes\General\Core;
class TwigExtension extends \Twig_Extension{

	protected $em;
	protected $gu;
	protected $gddc;

	public function __construct(ObjectManager $em,\TRC\CoreBundle\Systemes\General\GU $gu,
		\TRC\CoreBundle\Systemes\General\DDC $gddc){
	   
	   $this->em = $em;
	   $this->gu = $gu;
	   $this->gddc = $gddc;

	}

	public function getFunctions()
	{
		return array(
			'checkIfSpam' => new \Twig_Function_Method($this, 'isSpam'),
			'getEmploye' => new \Twig_Function_Method($this, 'getEmploye'),
			'getMonPoste' => new \Twig_Function_Method($this, 'getMonPoste'),
			'dureeLitterale' => new \Twig_Function_Method($this, 'dureeLitterale'),
			'monComite' => new \Twig_Function_Method($this, 'monComite'),
			'jaimeComite' => new \Twig_Function_Method($this, 'jaimeComite'),
			'jaimeReunion' => new \Twig_Function_Method($this, 'jaimeReunion'),
			'jaimePoint' => new \Twig_Function_Method($this, 'jaimePoint'),
			'secretariatComite' => new \Twig_Function_Method($this, 'secretariatComite'),
			'supprimerCommentaire' => new \Twig_Function_Method($this, 'supprimerCommentaire'),
			'monPoint' => new \Twig_Function_Method($this, 'monPoint'),
			'getMembreSecretariatComite' => new \Twig_Function_Method($this, 'getMembreSecretariatComite'),
			'getFonctionSecretariatComite' => new \Twig_Function_Method($this, 'getFonctionSecretariatComite'),
			'secretariatReunion' => new \Twig_Function_Method($this, 'secretariatReunion'),
			'chiffreComite' => new \Twig_Function_Method($this, 'chiffreComite'),
			'chiffreReunion' => new \Twig_Function_Method($this, 'chiffreReunion'),
			'messageLu' => new \Twig_Function_Method($this, 'messageLu'),
			'formatMontant' => new \Twig_Function_Method($this, 'formatMontant'),
			'tempsPoint' => new \Twig_Function_Method($this, 'tempsPoint'),
			'getBgAvancement' => new \Twig_Function_Method($this, 'getBgAvancement'),
			'jeSuisEnLigne' => new \Twig_Function_Method($this, 'jeSuisEnLigne'),
			'estMaDemat' => new \Twig_Function_Method($this, 'estMaDemat'),
			'estMonDocumentDemat' => new \Twig_Function_Method($this, 'estMonDocumentDemat'),
			'getServiceScenario' => new \Twig_Function_Method($this, 'getServiceScenario'),
			'estExecuteurDemat' => new \Twig_Function_Method($this, 'estExecuteurDemat'),
			'jeSuisResponsableService' => new \Twig_Function_Method($this, 'jeSuisResponsableService'),
			'aDejaDecideDemat' => new \Twig_Function_Method($this, 'aDejaDecideDemat'),
			'estMonDossierSNA' => new \Twig_Function_Method($this, 'estMonDossierSNA'),
			
		);
	}

	public function estMonDossierSNA(\TRC\CoreBundle\Entity\SNA\Dossier $dossier,\TRC\UserBundle\Entity\User $compte){

		return $this->gu->estMonDossierSNA($dossier,$compte);
	}

	public function formatMontant($montant){
		return Core::formatMontant($montant);
	}

	public function aDejaDecideDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\CoreBundle\Entity\Poste $poste){
		return $this->gu->aDejaDecideDemat($demat,$poste);

	}
	public function getServiceScenario(\TRC\CoreBundle\Entity\Core\Scenario $scenario){
		return $this->getServiceScenario($scenario);
	}

	public function estMonDocumentDemat(\TRC\CoreBundle\Entity\Demat\Document $document,\TRC\UserBundle\Entity\User $compte){

		return $this->gu->estMonDocumentDemat($document,$compte);
			
	}
	public function jeSuisResponsableService(\TRC\UserBundle\Entity\User $compte){
			return $this->gu->jeSuisResponsableService($compte);
	}
	public function estMaDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\UserBundle\Entity\User $compte){

		return $this->gu->estMaDemat($demat,$compte);	
	}

	public function estExecuteurDemat(\TRC\CoreBundle\Entity\Demat\Demat $demat,\TRC\UserBundle\Entity\User $compte){

		return $this->gu->estExecuteurDemat($demat,$compte);	
	}
	public function jeSuisEnLigne(\TRC\UserBundle\Entity\User $user){
		return $this->gu->jeSuisEnLigne($user);
	}
	public function getBgAvancement($taux){

		if($taux <= 30){
			return array('p'=>'red','box'=>'danger');
		}elseif ($taux <= 50) {
			return array('p'=>'maroon','box'=>'warning');;
			
		}elseif ($taux <= 75) {
			return array('p'=>'green disabled','box'=>'info');;
			
		}
		else{
			return array('p'=>'green','box'=>'success');
		}
		
	}

	public function tempsPoint(\TRC\CoreBundle\Entity\Point $point){
		return $this->gu->tempsPoint($point);
	}
	public function messageLu(\TRC\CoreBundle\Entity\Message $m,\TRC\UserBundle\Entity\User $moi){
		if($m->getReceive() == $moi)
		$m->setLu(true);
		$this->em->flush();
		//return true;
	}

	public function chiffreReunion(\TRC\CoreBundle\Entity\Reunion $reunion){
		return $this->gu->chiffreReunion($reunion);
	}
	public function chiffreComite(\TRC\CoreBundle\Entity\Comite $comite){
		return $this->gu->chiffreComite($comite);
	}
	public function getMonPoste(\TRC\CoreBundle\Entity\Utilisateur $poste){
		return $this->gu->getMonPoste($poste);
	}
	public function getMembreSecretariatComite(\TRC\CoreBundle\Entity\Comite $comite){
		return $this->gu->getMembreSecretariatComite($comite);
	}
	public function getFonctionSecretariatComite(\TRC\CoreBundle\Entity\Comite $comite){
		return $this->gu->getFonctionSecretariatComite($comite);
	}

	public function monPoint(\TRC\CoreBundle\Entity\Point $point,\TRC\UserBundle\Entity\User $user){
		return $this->gu->monPoint($point,$user);
	}
	public function supprimerCommentaire(\TRC\CoreBundle\Entity\Commentaire $com,\TRC\UserBundle\Entity\User $user){
		return $this->gu->supprimerCommentaire($com,$user);
	}
	public function secretariatComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){
		return $this->gu->secretariatComite($comite,$user);
	}
	public function secretariatReunion(\TRC\CoreBundle\Entity\Reunion $reunion,\TRC\UserBundle\Entity\User $user){
		return $this->gu->secretariatReunion($reunion,$user);
	}
	public function jaimeComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){

		return $this->gu->jaimeComite($comite,$user);
			
	}
	public function jaimeReunion(\TRC\CoreBundle\Entity\Reunion $comite,\TRC\UserBundle\Entity\User $user){

		return $this->gu->jaimeReunion($comite,$user);
			
	}
	public function jaimePoint(\TRC\CoreBundle\Entity\Point $comite,\TRC\UserBundle\Entity\User $user){

		return $this->gu->jaimePoint($comite,$user);
			
	}
	public function monComite(\TRC\CoreBundle\Entity\Comite $comite,\TRC\UserBundle\Entity\User $user){
		return $this->gu->monComite($comite,$user);
	}
	public function getEmploye(\TRC\UserBundle\Entity\User $compte){
		return $this->gu->getEmploye($compte);
	}
	public function isSpam($var){
		if(is_array($var))
			$length = count($var);
		elseif (is_string($var)) {
			$length = strlen($var);
			# code...
		}
		return 'traité :  nombre de caractère :'.$length;
	}

	public function getName()
	{
		return 'TwigExtension';
	}
	public function dureeLitterale(\DateTime $date,$fin = null){
		if(is_null($fin))
			$fin = new \DateTime();
		$tab = Core::nbreDeJours($date,$fin,"court");
		$t = explode(":", $tab);
		$str ="";
		if(intval($t[0]) > 0 ){
			if(intval($t[0]) < 2)
				$str = "un jour ";
			else
				$str = "".$t[0]." jours ";

			if(intval($t[1]) > 1)
				$str .= $t[1]." h";
		}
		elseif (intval($t[1]) > 0) {
			if(intval($t[1]) < 2)
				$str = "une heure";
			else
				$str = "".$t[1]." heures";
		}elseif (intval($t[2]) > 0) {

			if(intval($t[2]) < 2)
				$str = " une minute";
			else
				$str ="".$t[2]." minutes";
		}else
			$str = " à l'instant";


		return $str;
	}
	

}