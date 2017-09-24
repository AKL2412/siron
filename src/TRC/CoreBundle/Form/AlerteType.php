<?php

namespace TRC\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlerteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateOperation', 'date')
            ->add('montant','number')
            ->add('client','entity',array(
                'class'    => 'TRCCoreBundle:Client',
                'property' => 'Radical',
                'multiple' => false,
                'required'    => true,
                'empty_value' => "Choisissez le client",
                'empty_data'  => null,
                /*
                 'query_builder' => function(\TRC\CoreBundle\Repository\UtilisateurRepository  $r) use($options) {
                 return $r->getEmployeHorsComite($options['membres']);
                 }
                 //*/
                
            )
                )
                ->add('operation','entity',array(
                    'class'    => 'TRCCoreBundle:TypeOperation',
                    'property' => 'nom',
                    'multiple' => false,
                    'required'    => true,
                    'empty_value' => "Choisissez le type d'operation",
                    'empty_data'  => null,
                    /*
                     'query_builder' => function(\TRC\CoreBundle\Repository\UtilisateurRepository  $r) use($options) {
                     return $r->getEmployeHorsComite($options['membres']);
                     }
                     //*/
                    
                )
                    )
                ->add('save','submit', array('label' => 'Ajouter',
                    'attr'=>array('class'=>'btn btn-primary')))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'TRC\CoreBundle\Entity\Alerte'
        ));
    }
    /**
     * @return string
     */
    public function getName()
    {
        return 'trc_corebundle_alerte';
    }
    
}
