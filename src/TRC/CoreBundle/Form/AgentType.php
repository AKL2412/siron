<?php

namespace TRC\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom','text')
            ->add('prenom','text')
            ->add('email','email')
            ->add('entite','entity',array(
                'class'    => 'TRCCoreBundle:Entite',
                'property' => 'nom',
                'multiple' => false,
                'required'    => true,
                'empty_value' => "Choisissez l'entité",
                'empty_data'  => null,
                /*
                'query_builder' => function(\TRC\CoreBundle\Repository\UtilisateurRepository  $r) use($options) {
                return $r->getEmployeHorsComite($options['membres']);
                }
                //*/
                
                )
            )
            ->add('profil','entity',array(
                'class'    => 'TRCCoreBundle:Profil',
                'property' => 'nom',
                'multiple' => false,
                'required'    => true,
                'empty_value' => "Choisissez le profil",
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
            'data_class' => 'TRC\CoreBundle\Entity\Agent'
        ));
    }
    /**
     * @return string
     */
    public function getName()
    {
        return 'trc_corebundle_agent';
    }
    
}
