<?php 

namespace  TRC\CoreBundle\Systemes\Data;
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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use TRC\CoreBundle\Systemes\General\GU;

class Export
{
  protected $phpexcel;


  public function export($phpexcel,$datas,$nomfichier = null,$cheminSaugarde = null){

    $phpExcelObject = $phpexcel->createPHPExcelObject();
       $phpExcelObject->getProperties()->setCreator("Démat by Kouassi Leon ABY k.leon.aby@gmail.com")
           ->setLastModifiedBy("Démat by Kouassi Leon ABY k.leon.aby@gmail.com")
           ->setTitle("Office 2005 XLSX")
           ->setSubject("Office 2005 XLSX")
           ->setDescription("Document for Office 2005 XLSX, generated using PHP classes.")
           ->setKeywords("office 2005 openxml php")
           ->setCategory("Data generated|Démat");
        $activeSheet = 0;
      //  $sheet = $phpExcelObject->getActiveSheet();
        foreach ($datas as $sheetName => $dataSheet) {
          
          $objWorkSheet = $phpExcelObject->createSheet($activeSheet);
          $ligne = 1;
          //inserer les colonnes
          $this->insertLigne($objWorkSheet,$ligne,$dataSheet['colonne']);
          $ligne ++;
          // insertion des données
          foreach ($dataSheet['donnees'] as $key => $value) {
            $this->insertLigne($objWorkSheet,$ligne,$value);
            $ligne++;
          }
          $activeSheet++;
          $objWorkSheet->setTitle($sheetName);
          
        }

        $phpExcelObject->setActiveSheetIndex(0);
        // create the writer
        $writer = $phpexcel->createWriter($phpExcelObject, 'Excel5');
        if(is_null($cheminSaugarde))
          $writer->save('Excel-Generes/'.$nomfichier.".xls");
        else{

          $writer->save($cheminSaugarde.'/'.$nomfichier.".xls");
         // die('sauvegarde a : '.$cheminSaugarde.'/'.$nomfichier.".xls");
        }
        // create the response
        $response = $phpexcel->createStreamedResponse($writer);
        // adding headers
        if(is_null($nomfichier))
          $nomfichier = date("YmdHis").".xls";
        else
          $nomfichier .= " ".date("d-m-Y H:i:s").".xls";
        
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            //'stream-file.xls'
            $nomfichier
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    private function insertLigne($sheet,$line,$data){
      $colonne = "A";
      foreach ($data as $key => $v) {
        $sheet->setCellValue($colonne.$line, $v);
        $colonne++;
      }
      return $sheet;
    }


  public function explodeDocuments($documents){

    $colonne = array("Documents","Paramétré");
    $datas = array();
    foreach ($documents as $key => $value) {
      $temp = array($value->getNom());
      if(!is_null($value->getDocument()))
        $temp[] = "OUI";
      else
        $temp[] = "NON";
      $datas[] = $temp;

    }
    return array(
        'colonne'=>$colonne,
        "donnees" =>$datas
      );
  }
  public function explodeComments($documents){

    $colonne = array("Date","Message","Utilisateur","Entité","Fonction");
    $datas = array();
    foreach ($documents as $key => $value) {
      $temp = array(
        $value->getAt()->format('d/m/Y H:i'),
        $value->getMessage(),
        $value->getPoste()->getEmploye()->nomprenom(),
        $value->getPoste()->getService()->getNom(),
        $value->getPoste()->getFonction()->getNom()
        );
      $datas[] = $temp;

    }
    return array(
        'colonne'=>$colonne,
        "donnees" =>$datas
      );
  }

  public function explodeParametre($documents){

    $colonne = array("Paramètre","Valeur");
    $datas = array();
    foreach ($documents as $key => $value) {
      $temp = array(
        $value->getParametre()->getNom(),
        $value->getValeur()
        );
      $datas[] = $temp;

    }
    return array(
        'colonne'=>$colonne,
        "donnees" =>$datas
      );
  }

  public function explodeActions($documents){

    $colonne = array("Phase","Décision","Date d'affectation","Date de récupération","Date de fin","Commentaire","Fichier","Utilisateur","Entité","Fonction");
    $datas = array();
    foreach ($documents as $key => $value) {
      $fichier = "NON";
      if(!is_null($value->getFichier()))
        $fichier = "OUI";
      $temp = array(
        $value->getScenario()->getNom(),
        $value->getDecision()->getNom(),
        $value->getDateaffectation()->format('d/m/Y H:i'),
        $value->getDaterecuperation()->format('d/m/Y H:i'),
        $value->getDatefin()->format('d/m/Y H:i'),
        strip_tags($value->getCommentaire()),
        $fichier,
        $value->getPoste()->getEmploye()->nomprenom(),
        $value->getPoste()->getService()->getNom(),
        $value->getPoste()->getFonction()->getNom(),


        );
      $datas[] = $temp;

    }
    return array(
        'colonne'=>$colonne,
        "donnees" =>$datas
      );
  }

}