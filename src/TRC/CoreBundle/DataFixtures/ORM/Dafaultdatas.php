<?php
// src/Sdz/BlogBundle/DataFixtures/ORM/Categories.php

namespace TRC\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Entity\Phase;
use TRC\CoreBundle\Entity\Etat;
use TRC\CoreBundle\Entity\Utilisateur;
use TRC\UserBundle\Entity\User;
class Dafaultdatas implements FixtureInterface
{
// Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
	public function load(ObjectManager $manager){

		// Les phases et etat d'un DDC
		$phases = array(
			// la creation d'un ddc
			array(
				'nom'=>"Création",
				'description'=>"Un DDC est dans cette phase de sa création jusqu'à la validation dudit DDC par le Chef d'Agence (CA) ou le Responsable de Zone.",
				'code'=>'DDCCR',
				'etats'=> array(
							array(
								"nom"=>'DDC crée',
								'code'=>'DC',
								"description"=>"Le DDC est crée mais vide. Aucune information"
								),
							array(
								"nom"=>'DDC référencé systeme',
								'code'=>'DRS',
								"description"=>"une référence système a été affecté au DDC"
								),
							array(
								"nom"=>'DDC référencé client',
								'code'=>'DRC',
								"description"=>"une référence client a été affecté au DDC, le DDC peut être recherché par le radical du client."
								),
							array(
								"nom"=>"DDC validé par l'Agent Dossier (ADO)",
								'code'=>'DVADO',
								"description"=>"L'agent de dossier a fini la saisie ainsi le chargement des fichiers liés au DDC."
								)
					)
				),
			array(
				'nom'=>"Validation",
				'description'=>"Un DDC est dans cette phase du partage jusqu'à la validation ou rejet dudit DDC par un analyste.",
				'code'=>'DDCVA',
				'etats'=> array(
							array(
								"nom"=>"DDC validé par l'Analyste",
								'code'=>'DVA',
								"description"=>"L'analyste a vérifié le DDC."
								)
					)
				),
			);
		
		foreach($phases as $key => $phase)
		{
			$phase_ = new Phase();
			
			foreach ($phase as $attr => $value) {
				$method = 'set'.ucfirst($attr);
				if(method_exists($phase_, $method))
				$phase_->$method($value);
			}

			// On la persiste
			$manager->persist($phase_);

			// Ajout des etat

			foreach($phase['etats'] as $key => $etat)
			{
				$etat_ = new Etat();
				$etat_->setPhase($phase_);
				foreach ($etat as $attr => $value) {
					$method = 'set'.ucfirst($attr);
					if(method_exists($phase_, $method))
					$etat_->$method($value);
				}
				// On la persiste
				$manager->persist($etat_);
			}
		}

		$user = new User();
		$user->setUsername('admin');
        $user->setPlainPassword('admin');
        $user->setEmail("admin@admin.com");
        $user->setRoles(array("ROLE_SUPER_ADMIN"));
        $user->setEnabled(true);

		$u = new Utilisateur();
		$u->setNom("Nom de l'administrateur");
		$u->setPrenom("Prenom de l'administrateur");
		$u->setEmail("admin@admin.com");
		$u->setImage("img/default.png");
		$u->setCin('AS7845OL');
		$u->setAdresse("Adresse de l'administrateur");
		$u->setCompte($user);
		$u->setDateajout(new \DateTime());
		
		
		$manager->persist($u);
		


		$manager->flush();
	}
}