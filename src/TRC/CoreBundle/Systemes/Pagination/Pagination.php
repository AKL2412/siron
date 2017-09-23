<?php
// src/Sdz/BlogBundle/Antispam/SdzAntispam.php
namespace TRC\CoreBundle\Systemes\Pagination;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
class Pagination
{
	protected $em;

	public function setEntityManager(ObjectManager $em){
	   $this->em = $em;
	}

	public function pagination($objet,$page,$url,$urlRoute,$criteres,$nbreElements,$sup = null){
		
		$extension = "";
		if($sup !== null)
			$extension = "&".$sup;
		$objet = ucfirst($objet);

		$repo = $this->em->getRepository('TRCCoreBundle:'.$objet);

		$total = count($repo->findBy(
                $criteres,
                array(),null,0
                ));

		$dernierePage = round($total/$nbreElements);
        if($dernierePage < ($total/$nbreElements))
            $dernierePage+=1;

        $pagination = array();
	

			if($dernierePage > 4 ){
				if($page < 4 ){
					for ($i=1; $i <= 4; $i++) { 
						$pagination[] = array(
							'url'=>$url."?p=".$i.$extension,
							"libelle"=>$i
						);
					}
					if(($page +2) <  $dernierePage){
						$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					}
						
						$pagination[] = array(
							'url'=>$url."?p=".$dernierePage.$extension,
							"libelle"=>$dernierePage
						);
				}elseif ($page > 3) {
					$pagination[] = array(
							'url'=>$url."?p=1".$extension,
							"libelle"=>"1"
						);
					$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					$pagination[] = array(
							'url'=>$url."?p=".($page-1).$extension,
							"libelle"=>($page-1)
						);
					$pagination[] = array(
							'url'=>$url."?p=".$page.$extension,
							"libelle"=>$page
						);
					if($page+1 < $dernierePage){
						$pagination[] = array(
							'url'=>$url."?p=".($page+1).$extension,
							"libelle"=>($page+1)
						);
						$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					}
					if($page !=  $dernierePage)
					$pagination[] = array(
							'url'=>$url."?p=".$dernierePage.$extension,
							"libelle"=>$dernierePage
						);

				}
			}else{
				for ($i=1; $i <= $dernierePage; $i++) { 
					$pagination[] = array(
							'url'=>$url."?p=".$i.$extension,
							"libelle"=>$i
						);
				}
			}

			return array(
				"pagination"=>$pagination,
				"page"=>$page,
				"last"=>$dernierePage,
				"sup"=>$sup,
				'url'=>$url,
				'urlRoute'=>$urlRoute,
				'extension'=>$extension);
		


	}
	public function pagination2($page,$url,$urlRoute,$criteres,$nbreElements,$sql,$sup = null){
		
		$extension = "";
		if($sup !== null)
			$extension = "&".$sup;
		//$objet = ucfirst($objet);

		$query = $this->em->createQuery($sql);
        $query->setParameters($criteres);
        //$query->setFirstResult($id)->setMaxResults($nbre);
		//$repo = $this->em->getRepository('TRCCoreBundle:'.$objet);

		$total = count($query->getResult());

		$dernierePage = round($total/$nbreElements);
        if($dernierePage < ($total/$nbreElements))
            $dernierePage+=1;

        $pagination = array();
	

			if($dernierePage > 4 ){
				if($page < 4 ){
					for ($i=1; $i <= 4; $i++) { 
						$pagination[] = array(
							'url'=>$url."?p=".$i.$extension,
							"libelle"=>$i
						);
					}
					if(($page +2) <  $dernierePage){
						$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					}
						
						$pagination[] = array(
							'url'=>$url."?p=".$dernierePage.$extension,
							"libelle"=>$dernierePage
						);
				}elseif ($page > 3) {
					$pagination[] = array(
							'url'=>$url."?p=1".$extension,
							"libelle"=>"1"
						);
					$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					$pagination[] = array(
							'url'=>$url."?p=".($page-1).$extension,
							"libelle"=>($page-1)
						);
					$pagination[] = array(
							'url'=>$url."?p=".$page.$extension,
							"libelle"=>$page
						);
					if($page+1 < $dernierePage){
						$pagination[] = array(
							'url'=>$url."?p=".($page+1).$extension,
							"libelle"=>($page+1)
						);
						$pagination[] = array(
							'url'=>"#",
							"libelle"=>"..."
						);
					}
					if($page !=  $dernierePage)
					$pagination[] = array(
							'url'=>$url."?p=".$dernierePage.$extension,
							"libelle"=>$dernierePage
						);

				}
			}else{
				for ($i=1; $i <= $dernierePage; $i++) { 
					$pagination[] = array(
							'url'=>$url."?p=".$i.$extension,
							"libelle"=>$i
						);
				}
			}

			return array(
				"pagination"=>$pagination,
				"page"=>$page,
				"last"=>$dernierePage,
				"sup"=>$sup,
				'url'=>$url,
				'urlRoute'=>$urlRoute,
				'extension'=>$extension);
		


	}

}
