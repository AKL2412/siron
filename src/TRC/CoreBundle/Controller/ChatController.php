<?php

namespace TRC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ChatController extends Controller
{
    public function affichageAction()
    {
        return $this->render('TRCCoreBundle:Chat:affichage.html.twig');
    }
}