<?php 
namespace  TRC\CoreBundle\Systemes\Twig;

use Doctrine\Common\Persistence\ObjectManager;
//use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use TRC\CoreBundle\Systemes\General\GU;
use TRC\CoreBundle\Systemes\General\Core;
/**
* 
*/
class Traitement 
{
	protected $em;
	public function __construct(ObjectManager $em){
	   
	   $this->em = $em;

	}

	public function cetdos(\TRC\CoreBundle\Entity\Central\CETDOS $cetdos){

		return $cetdos->etape();
	}

}