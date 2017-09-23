<?php
namespace  TRC\CoreBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use TRC\CoreBundle\Entity\Agence;
use TRC\CoreBundle\Entity\BOC;
use TRC\CoreBundle\Entity\CIC;
use TRC\CoreBundle\Entity\Entite;

class AddSup
{

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        // only act on some "Product" entity
        if (!$entity instanceof Agence
        	||
        	!$entity instanceof BOC
        	||
        	!$entity instanceof CIC) {
            return;
        }
        $entite = new Entite();
        $entite->setClasse(get_class($entity));
        $entity->setEntite($entite);
        $entityManager = $args->getEntityManager();
        $entityManager->persist($entity);
        $entityManager->flush();
        // ... do something with the Product
    }
}
?>