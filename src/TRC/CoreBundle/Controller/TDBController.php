<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use TRC\CoreBundle\Systemes\General\Core;
class TDBController extends Controller
{
	public function searchddsAction(Request $request){

		try {
			if(!$request->isMethod("GET"))
				throw new \Exception("Requête POST, Désolé!!!", 1);
			extract($_GET);
			if(!isset($decision))
				$decision = null;
			if(!isset($credit))
				$credit = null;
			$periodes = array();
			if($periode == 'mois')
				$periodes = Core::leMois();
			elseif ($periode == 'semaine') 
				$periodes = Core::laSemaine();
			elseif ($periode == 'trimestre') 
				$periodes = Core::leTrimestre();
			elseif ($periode == 'semestre') 
				$periodes = Core::leSemestre();
			$em = $this->get('doctrine')->getManager();
			$p = 1;
	        $nbre = 5;
	        if( 
	            $request->query->get('p')!== null 
	            && 
	            !empty($request->query->get('p'))
	          ){
	                $p = $request->query->get('p');
	        }
	        $id = ($p-1)*$nbre;
	        if($id < 0)
	            $id = 0;
		//************** Decision ********************
        $decisions = array();

        $sql = 'SELECT DISTINCT d FROM TRCCoreBundle:DDC\Decision d  WHERE d.final = :final  ';    
        $criteres = array('final'=>true);
        $query = $em->createQuery($sql);
        $query->setParameters($criteres);
        //$query->setFirstResult($id)->setMaxResults($nbre);
        $decisions = $query->getResult();
        //**********************************************************

		$dec = $em->getRepository('TRCCoreBundle:DDC\Decision')
					->findOneByCode($decision);
		$tdc = $em->getRepository('TRCCoreBundle:DDC\TDC')
					->findOneByCode($credit);
		$codecentre = 'CSCAD';
		$sql ="SELECT DISTINCT p FROM TRCCoreBundle:Central\PTEDDC p JOIN p.agence a JOIN p.ddc dd JOIN p.decision d WHERE p.codecentre = :codecentre and d IN (:decisions) and p.datedecision >= :debut and p.datedecision <= :fin";
		$criteres = array('decisions'=>$decisions,'codecentre'=>$codecentre);
		$results = array();
		$sup = null;
		if(!is_null($dec) && !is_null($tdc)){
			$sql = 'SELECT DISTINCT p FROM TRCCoreBundle:Central\PTEDDC p JOIN p.agence a JOIN p.decision d JOIN p.ddc dd JOIN dd.tdc t WHERE p.codecentre = :codecentre and t = :tdc and d = :dec  and p.datedecision >= :debut and p.datedecision <= :fin';    
        	$criteres = array('tdc'=>$tdc,'dec'=>$dec,'codecentre'=>$codecentre);
        	$sup = "credit=".$credit."&decision=".$decision;
        	
		}elseif(is_null($dec) && !is_null($tdc)){
			$sql = 'SELECT DISTINCT p FROM TRCCoreBundle:Central\PTEDDC p JOIN p.agence a JOIN p.ddc dd JOIN dd.tdc t WHERE p.codecentre = :codecentre and t = :tdc and p.datedecision >= :debut and p.datedecision <= :fin';    
        	$criteres = array('tdc'=>$tdc,'codecentre'=>$codecentre);
        	$sup = "credit=".$credit;
		}elseif(!is_null($dec) && is_null($tdc)){
			$sql = 'SELECT DISTINCT p FROM TRCCoreBundle:Central\PTEDDC p JOIN p.agence a JOIN p.ddc dd JOIN p.decision d  WHERE p.codecentre = :codecentre and d = :dec  and p.datedecision >= :debut and p.datedecision <= :fin';    
        	$criteres = array('dec'=>$dec,'codecentre'=>$codecentre);
        	$sup = "decision=".$decision;
        	
		}
		$gu = $this->get('trc_core.gu');
		$user = $this->getUser();
		$utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
						->findOneByCompte($user);
		$fonction = $gu->fonction($utilisateur);

		if(!is_null($fonction) && !is_null($fonction->getProfil())){
			$codeprofil = $fonction->getProfil()->getCode();
			if($codeprofil == 'CC'){
				$sql .=" and dd.fonction = :fonction";
				$criteres['fonction']=$fonction;
			}elseif($codeprofil == 'CA'){
				$agence = $gu->getEntite($fonction->getEntite());
				$sql .=" and p.agence = :agence";
				$criteres['agence']=$agence;
			}elseif($codeprofil == 'RZ'){
				$zone = $gu->getEntite($fonction->getEntite());
				$sql .=" and a.zone= :zone";
				$criteres['zone']=$zone;
			}
		}

		$sql .= " ORDER BY p.datedecision DESC ";
		$criteres['debut']=$periodes['debut'];
		$criteres['fin']=$periodes['fin'];
		$query = $em->createQuery($sql);
        $query->setParameters($criteres);
        $query->setFirstResult($id)->setMaxResults($nbre);
        $results = $query->getResult();

        $servicePagination = $this->get('trc_core.pagination');
		$url = $this->generateUrl('trc_core_search_ddcs');
        $urlRoute = 'trc_core_search_ddcs';
        $sup .="&periode=".$periode;
        $pagination = $servicePagination->pagination2($p,$url,$urlRoute,$criteres,$nbre,$sql,$sup);

		return $this->render('TRCCoreBundle:TDB:search.html.twig',
			array('results' => $results,
				"pagination"=>$pagination,
				"periodes"=>$periodes));
		} catch (\Exception $e) {
			throw new \Exception("Erreur : ".$e->getMessage(), 1);
			
		}
		
	}

	public function moncompteAction(Request $request){

		$user = $this->getUser();
		$em = $this->get('doctrine')->getManager();
		$gu = $this->get('trc_core.gu');
		$utilisateur = $em->getRepository('TRCCoreBundle:GSC\Gestionnaire')
					->findOneByCompte($user);
		/*
		if(is_null($utilisateur))
			throw new \Exception("Erreur de compte", 1);
		//*/
		if ($request->isMethod('POST')) {
			
			extract($request->request->all());
			/*
			if(strlen($cpassword) < 7)
				throw new \Exception("Le mot de passe doit avoir au moins 7 caractères", 1);
			//*/
			//die($cpassword." == ".$confpassword);
			if($cpassword != $confpassword)
				throw new \Exception("Mot de passe non conforme", 1);

			$user->setPlainPassword($cpassword);
			$user->setEnabled(false);
			//$em->persist($utilisateur->getCompte());
			$em->flush();

			$user->setEnabled(true);
			$em->flush();
			return $this->redirect($this->generateUrl('fos_user_security_logout'));
				
		}

		return $this->render('TRCCoreBundle:TDB:moncompte.html.twig',
			array(
				"compte"=>$user
				));
	}
}