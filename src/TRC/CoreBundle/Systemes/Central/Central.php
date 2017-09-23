<?php 

namespace  TRC\CoreBundle\Systemes\Central;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Entity\Journal as JournalEntity;
use Symfony\Component\HttpFoundation\File\File;
use TRC\UserBundle\Entity\User as Compte;
use TRC\CoreBundle\Entity\Acteur as Actor;

use TRC\CoreBundle\Entity\Central\PTEDDC;
use TRC\CoreBundle\Entity\Central\CETDOS;
use TRC\CoreBundle\Entity\Central\CVCA;
use TRC\CoreBundle\Entity\Central\CVRZ;
use TRC\CoreBundle\Entity\Central\CABOC;
use TRC\CoreBundle\Entity\Central\CVRBOC;
use TRC\CoreBundle\Entity\Central\CCIC;
use TRC\CoreBundle\Entity\Central\CVDO;
use TRC\CoreBundle\Entity\Central\CSCAD;

use TRC\CoreBundle\Systemes\General\GU;

class Central
{

    protected $em;
    protected $gu;
	public function setEntityManager(ObjectManager $em,\TRC\CoreBundle\Systemes\General\GU $gu){
	   $this->em = $em;
	   $this->gu = $gu;
	}

	public function ddcAuPTEDDC(\TRC\CoreBundle\Entity\DDC\DDC $ddc,
		\TRC\CoreBundle\Entity\Agence $agence
		){
		$newDDC = new PTEDDC();
		$newDDC->setDdc($ddc);
		$newDDC->setAgence($agence);
		$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$this->em->flush();
		return true;
	}

	public function ddcAuCETDOS(\TRC\CoreBundle\Entity\DDC\DDC $ddc,
		\TRC\CoreBundle\Entity\Agence $agence
		){
		$newDDC = new CETDOS();
		$newDDC->setDdc($ddc);
		$newDDC->setTermine(false);
		$newDDC->setAgence($agence);
		$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($ddc);
		$pteddc->setCentre("Etude de dossier [Agence]");
		$pteddc->setCodecentre("CETDOS");
		$pteddc->setClasse(get_class($newDDC));
		$this->em->flush();
		return true;
	}

	public function ddcAuCVCA(\TRC\CoreBundle\Entity\Central\CETDOS $cetdos
		){
		$newDDC = new CVCA();
		$newDDC->setDdc($cetdos->getDdc());
		$newDDC->setTermine(false);
		$newDDC->setAgence($cetdos->getAgence());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cetdos->getDdc());
		$pteddc->setCentre("Validation du Chef d'Agence #VCA[Agence]");
		$pteddc->setCodecentre("CVCA");
		$pteddc->setClasse(get_class($newDDC));
		$cetdos->setTermine(true);
		$cetdos->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCVRZ(\TRC\CoreBundle\Entity\Central\CVCA $cvca
		){
		$newDDC = new CVRZ();
		$newDDC->setDdc($cvca->getDdc());
		$newDDC->setTermine(false);
		$newDDC->setZone($cvca->getAgence()->getZone());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvca->getDdc());
		$pteddc->setCentre("Validation du Responsable de Zone #VRZ[Zone]");
		$pteddc->setCodecentre("CVRZ");
		$pteddc->setClasse(get_class($newDDC));
		$cvca->setTermine(true);
		$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCABOCdCA(\TRC\CoreBundle\Entity\Central\CVCA $cvca
		){
		$newDDC = new CABOC();
		$newDDC->setDdc($cvca->getDdc());
		$newDDC->setTermine(false);
		//$newDDC->setZone($cvca->getAgence()->getZone());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvca->getDdc());
		$pteddc->setCentre("Analyse Back Office Crédits #ABOC[BOC]");
		$pteddc->setCodecentre("CABOC");
		$pteddc->setClasse(get_class($newDDC));
		$cvca->setTermine(true);
		$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCABOC(\TRC\CoreBundle\Entity\Central\CVRZ $cvca
		){
		$newDDC = new CABOC();
		$newDDC->setDdc($cvca->getDdc());
		$newDDC->setTermine(false);
		//$newDDC->setZone($cvca->getAgence()->getZone());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvca->getDdc());
		$pteddc->setCentre("Analyse Back Office Crédits #ABOC[BOC]");
		$pteddc->setCodecentre("CABOC");
		$pteddc->setClasse(get_class($newDDC));
		$cvca->setTermine(true);
		$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCVRBOC(\TRC\CoreBundle\Entity\Central\CABOC $cvca
		){
		$newDDC = new CVRBOC();
		$newDDC->setDdc($cvca->getDdc());
		$newDDC->setTermine(false);
		$newDDC->setBoc($cvca->getBoc());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvca->getDdc());
		$pteddc->setCentre("Validation du responsable du back office crédits #VRBOC[BOC]");
		$pteddc->setCodecentre("CVRBOC");
		$pteddc->setClasse(get_class($newDDC));
		$cvca->setTermine(true);
		$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCCIC(\TRC\CoreBundle\Entity\Central\CVRBOC $cvca
		){
		$newDDC = new CCIC();
		$newDDC->setDdc($cvca->getDdc());
		$newDDC->setTermine(false);
		//$newDDC->setBoc($cvca->getBoc());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvca->getDdc());
		$pteddc->setCentre("Etude du dossier par le Comité Interne de Crédits #CCIC[CIC]");
		$pteddc->setCodecentre("CCIC");
		$pteddc->setClasse(get_class($newDDC));
		$cvca->setTermine(true);
		$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcAuCVDO(\TRC\CoreBundle\Entity\DDC\DDC $ddc
		){
		$newDDC = new CVDO();
		$newDDC->setDdc($ddc);
		$newDDC->setTermine(false);
		//$newDDC->setBoc($cvca->getBoc());
		//$newDDC->setFonction($ddc->getFonction());
		$this->em->persist($newDDC);
		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($ddc);
		$pteddc->setCentre("Direction des opérations #DO[Siège]");
		$pteddc->setCodecentre("CVDO");
		$pteddc->setClasse(get_class($newDDC));
		//$cvca->setTermine(true);
		//$cvca->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}
	public function ddcRejete(\TRC\CoreBundle\Entity\DDC\DDC $ddc,
		\TRC\CoreBundle\Entity\DDC\Decision $decision,
		$classeDeRefus
		){

		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($ddc);
		$newDDC = new CSCAD();
		$newDDC->setPteddc($pteddc);
		$newDDC->setDdc($ddc);
		$pteddc->setCentre("Suivi et Archivage #CAD[Siège]");
		$pteddc->setCodecentre("CSCAD");
		$pteddc->setClasse($classeDeRefus);
		$pteddc->setDecision($decision);
		$pteddc->setDatedecision(new \DateTime());
		$this->em->persist($newDDC);
		$this->em->flush();
		return true;
	}
	public function ddcDebloque(\TRC\CoreBundle\Entity\Central\CVDO $cvdo
		){

		$decision = $cvdo->getDecision();

		$pteddc = $this->em->getRepository('TRCCoreBundle:Central\PTEDDC')
				->findOneByDdc($cvdo->getDdc());
		$newDDC = new CSCAD();
		$newDDC->setDebloque(true);
		$newDDC->setPteddc($pteddc);
		$newDDC->setDdc($cvdo->getDdc());
		$pteddc->setCentre("Suivi et Archivage #CAD[Siège]");
		$pteddc->setCodecentre("CSCAD");
		$pteddc->setClasse(get_class($cvdo));
		$pteddc->setDecision($decision);
		$pteddc->setDatedecision(new \DateTime());
		$this->em->persist($newDDC);
		$cvdo->setTermine(true);
		$cvdo->setDatefin(new \DateTime());
		$this->em->flush();
		return true;
	}

	public function debutColDoc(\TRC\CoreBundle\Entity\DDC\DDC $ddc
		){

		$cetdos = $this->em->getRepository('TRCCoreBundle:Central\CETDOS')
				->findOneByDdc($ddc);
		$cetdos->setDebutcoldoc(new \DateTime());
		$this->em->flush();
		return true;
	}


	public function donneesExport(array $datas){

		$table = array();

		$colonnes = array(
			"Référence",
			"Racine client",
			"Intitulé du client",
			"Intitulé agence",
			"Code agence",
			"Code crédit",
			"Intitulé du crédit",
			"Type de dossier",
			"Matricule de l'agent",
			"Nom et prenoms de l'agent",
			// Carateristique de dossier
			"Montant demandé",
			"Durée demandée",
			"Quotité",
			"Décision",
			"Montant accordé",
			"Durée accordée",
			"Quotité accordée",
			);

		$donnees = array();

		foreach ($datas as $key => $pteddc) {
			$ddc = $pteddc->getDdc();


			$realisateur = $this->gu->getParentActeur($ddc->getFonction()->getActeur());
			$intuleclient = "Inconnu";
			if(!is_null($ddc->getClient()->getIntitule()))
				$intuleclient = $ddc->getClient()->getIntitule();

			$decision = "Inconnu";
			$ma = $da = $qa = "0";
			if(!is_null($pteddc->getDecision())){
				$decision = $pteddc->getDecision()->getNom();

			}
			if(!is_null($pteddc->getDeblocage())){
				$ma = $pteddc->getDeblocage()->getCec()->getMontant();
				$da = $pteddc->getDeblocage()->getCec()->getDuree();
				$qa = $pteddc->getDeblocage()->getCec()->getMensualite();
			}
			$temp = array(
				$ddc->getRc(),
				$ddc->getClient()->getRadical(),
				$intuleclient,
				$ddc->getClient()->getAgence()->getNom(),
				$ddc->getClient()->getAgence()->getCode(),
				$ddc->getTdc()->getCode(),
				$ddc->getTdc()->getNom(),
				$ddc->getTddc()->getNom(),
				$realisateur->getMatricule(),
				$realisateur->getNom()." ".$realisateur->getPrenom(),
				$ddc->getCdcd()->getMontant(),
				$ddc->getCdcd()->getDuree(),
				$ddc->getCdcd()->getQuotite(),
				$decision,
				$ma,
				$da,
				$qa
				);

			$donnees[] = $temp;
		}
		$table['colonne'] = $colonnes;
		$table['donnees'] = $donnees;
		return array(
				"Données"=>$table
			);
	}

}