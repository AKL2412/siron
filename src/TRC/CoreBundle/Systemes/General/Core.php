<?php 

namespace  TRC\CoreBundle\Systemes\General;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

class Core{

	static public function slugify($text){
  // replace non letter or digits by -
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);

  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  // trim
  $text = trim($text, '-');

  // remove duplicate -
  $text = preg_replace('~-+~', '-', $text);

  // lowercase
  $text = strtolower($text);

  if (empty($text)) {
    return 'n-a';
  }

  return $text;
}

public static function detailDemat($demat){
  $instance = $demat->getInstance();
  $objet = $instance->getObjet();
  $auteur = $demat->getAuteur()->getEmploye();
  if(!is_null($demat->getExecuteur()))
    $executeur = $demat->getExecuteur()->getEmploye();

  $str = '<p>'.$objet->getNom()." / ".$instance->getNom().'</p>';
  $str .= '<p><b>'.$auteur->getPrenom()." ".strtoupper($auteur->getNom()).'</b></p>';
  if(!is_null($demat->getExecuteur()))
  $str .= '<p> <b>Actuellement</b>:'.$executeur->getPrenom()." ".strtoupper($executeur->getNom()).'</p>';
  

  return $str;
}
public static function tousLesfichiers($path,$array){
       
        foreach (glob($path."*") as $f) {
            
                if(is_file($f)){
                   $array[] = $f;
                }
               elseif (is_dir($f)) {
                   $array = Core::tousLesfichiers($f."/",$array);
               }
               
        }
        return $array;
    }

    public static function formatMontant($montant){
    	$strmontant = $montant."";
    	$t = explode(".", $strmontant);
    	$strmontant = $t[0];
    	$reste = "";
    	if(count($t) == 2)
    		$reste = ".".$t[1];
    	elseif (count($t) < 2) {

    		$reste = ".00";
        if(strlen($montant) < 1 )
          $reste = "0.00";
    	}
    	$t = 0;
    	$str = "";
    	for ($i=strlen($strmontant) - 1 ; $i >= 0 ; $i--) { 
    		$str .= $strmontant[$i];
    		$t+=1;
    		if($t == 3 && $i > 0){
    			$str .=" ";
    			$t = 0;
    		}
    	}
    	
    	return strrev($str).$reste;
    }

  public static function formatRacine($racine){

    $racine = Core::bourrage($racine).$racine;

    return $racine;
  }
  public static function bourrage($n){
    $str = "";
    $t = 6 - strlen($n."");
    for ($i=0; $i < $t ; $i++) { 
     $str .= "0";
    }
    return $str;
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

  public static function generateClePrive() {
    $length = rand(9,20);
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&#~-\=_@';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

  public static function complexiteCle($cle){
        $caracspeci = "&#@\_-";
        $nombre = "0123456789";
        $alphamin = "abcdefghijklmnopqrstuvwxyz";
        $alphamaj = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if(
          strlen($cle) > 8 &&
          Core::cleOk($caracspeci,$cle) && 
          Core::cleOk($nombre,$cle) && 
          Core::cleOk($alphamaj,$cle)  
          )
          return true;

        return false;
    }

    public static function cleOk($chaine,$cle){

      for ($i=0; $i < strlen($cle) ; $i++) { 
        for ($j=0; $j < strlen($chaine); $j++) { 
          if($cle[$i] == $chaine[$j])
            return true;
        }
      }
      return false;
    }

  public static function generateRandomString($length = 4) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

public static function nbreDeJours($debut,$fin,$court=null){
    $d = $debut->format('Y-m-d H:i:s');
        $f = $fin->format('Y-m-d H:i:s');
            $times = strtotime($f) - strtotime($d);
            $r = ($times%86400);
            $jour = ($times - $r) / 86400;
            $rh = ($r%3600);
            $heure = ($r-$rh)/3600;
            $rm = ($rh%60);
            $minu = ($rh-$rm)/60;
            $sec = $rm;

            if(!is_null($court))
              $str= Core::doubleChiffre($jour).":".Core::doubleChiffre($heure).":".Core::doubleChiffre($minu).":".Core::doubleChiffre($sec);
              else
              $str= Core::doubleChiffre($jour)." J ".Core::doubleChiffre($heure)." H ".Core::doubleChiffre($minu)." m ".Core::doubleChiffre($sec)." s";
            return $str;
  }

  public static function doubleChiffre($nbre){
    if($nbre < 10)
      return "0".$nbre;
    return $nbre;
  }

public static function pointToArray($point){

    $datedebut = "";
    if(!is_null($point->getDatedebut()))
      $datedebut = $point->getDatedebut()->format('d-m-Y');

    return array(
      'comite'=>$point->getReunion()->getComite()->getCode(),
      'reunion'=>$point->getReunion()->getCode(),
      'point'=>$point->getCode(),
      'action'=>$point->getTitre(),
      'description'=>$point->getDetail(),
      'priorite'=>$point->getNiveau()->getNom(),
      'état'=>$point->getEtat()->getNom(),
      'date de debut'=>$datedebut,
      'échéance'=>$point->getDeadline()->format('d-m-Y'),
      '%acheve'=>$point->getTaux()
      );
  }
}