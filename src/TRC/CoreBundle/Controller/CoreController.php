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

class CoreController extends Controller{

	public function voirDetailsPhaseAction(Request $request,$id){

		$em = $this->get('doctrine')->getManager();
		$datas=array();
		$sysgu = $this->get('trc_core.gu');
		try {

			if(!$request->isMethod('POST'))
				throw new \Exception("Error Processing Request :: Le type requête", 1);
				
			$pddc = $em->getRepository('TRCCoreBundle:DDC\PDDC')
					->find($id);
			if(is_null($pddc))
				throw new \Exception("ERREUR ! Cette phase n'existe plus");
			
			$datas = $this->detailPhase($pddc);

		} catch (\Exception $e) {
			$datas['code'] = -1;
			$datas['titre']  = "Une erreur survenue !!";
			$datas['message'] = $e->getMessage();
			
		}
		$response = new Response();
        $response->setContent(json_encode($datas));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
	}

	private function detailPhase(\TRC\CoreBundle\Entity\DDC\PDDC $pddc){

		$em = $this->get('doctrine')->getManager();
		$datas=array();
		$sysgu = $this->get('trc_core.gu');
		try {
			$phase =  $pddc->getPhase();
			$titre = '#'.$phase->getCode().' '.$phase->getNom();
			$code = 1;
			$message = "";
			$eddcs = $em->getRepository('TRCCoreBundle:DDC\EDDC')
					->findBy(
						array("pddc"=>$pddc),
						array("dateajout"=>"asc"),null,0);
			foreach ($eddcs as $key => $eddc) {
				if($eddc->getVerdict()
					&& !is_null($eddc->getDecision())
					){
					$real = $sysgu->getParentActeur($eddc->getFonction()->getActeur());
					$message .= '<p > <h6 class="text-success">'.$eddc->getEtat()->getNom().'</h6>'.
						'Décision : <span class="label label-danger">'.$eddc->getDecision()->getNom().'</span><br>Commentaire<p class="well">'.
						$eddc->getCommentaire().'</p><b><i><u>'.$real->getPrenom().' '.strtoupper($real->getNom()).'</u></i></b><p>';
				}
			}

			$message .= '<p><small>'.$pddc->getDatedebut()->format('d/m/Y H:i:s').' -  '.$pddc->getDatefin()->format('d/m/Y H:i:s').'</small></p>';

		} catch (\Exception $e) {
			$code = -1;
			$titre = "Une erreur survenue !!";
			$message = $e->getMessage();
			
		}
		$datas['code'] = $code;
		$datas['titre'] = $titre;
		$datas['message'] = $message;
		return $datas;
	}

	public function supprimernotifAction(){

		$em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->findOneByCompte($user);
        if(is_null($utilisateur))
            throw new \Exception("Erreur de compte", 1);
            
        $moi = $utilisateur->getActeur();
        $criteres = array("moi"=>$moi);
		$sql = 'SELECT n FROM TRCCoreBundle:Notification n WHERE n.acteur = :moi ORDER BY n.lu ASC,n.datenoti DESC';
        $query = $em->createQuery($sql);
        $query->setParameters($criteres);

        $notifications = $query->getResult();
        foreach ($notifications as $key => $notif) {
        	$em->remove($notif);
        }

        $em->flush();

        return $this->redirect($this->generateUrl('trc_core_notifications'));
	}

	public function marquernotifAction(){

		$em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $utilisateur = $em->getRepository('TRCCoreBundle:Utilisateur')
                        ->findOneByCompte($user);
        if(is_null($utilisateur))
            throw new \Exception("Erreur de compte", 1);
            
        $moi = $utilisateur->getActeur();
        $criteres = array("moi"=>$moi);
		$sql = 'SELECT n FROM TRCCoreBundle:Notification n WHERE n.acteur = :moi ORDER BY n.lu ASC,n.datenoti DESC';
        $query = $em->createQuery($sql);
        $query->setParameters($criteres);

        $notifications = $query->getResult();
        foreach ($notifications as $key => $notif) {
        	$notif->setLu(true);
        }

        $em->flush();

        return $this->redirect($this->generateUrl('trc_core_notifications'));
	}
}