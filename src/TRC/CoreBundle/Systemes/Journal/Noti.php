<?php 

namespace  TRC\CoreBundle\Systemes\Journal;
use Doctrine\Common\Persistence\ObjectManager;
use TRC\CoreBundle\Entity\Notification;
use TRC\CoreBundle\Entity\Message;
use TRC\CoreBundle\Systemes\General\Core;
use TRC\CoreBundle\Systemes\General\GU;
class Noti{

	protected $em;
	protected $gu;
	public function setEntityManager(ObjectManager $em,GU $gu){
	   $this->em = $em;
	   $this->gu = $gu;
	}
	public function notifier($array){
		$j = new Notification();
		$j->setUser($array['user']);
		$j->setTitre($array['titre']);
		$j->setContenu($array['contenu']);
		$this->em->persist($j);
		$this->em->flush();
		return true;
	}
	public function texto($array){
		$j = new Message();
		$j->setSend($array['send']);
		$j->setReceive($array['receive']);
		$j->setContenu($array['contenu']);
		$this->em->persist($j);
		$this->em->flush();
		return true;
	}

	
}