<?php

namespace App\Form;

use App\Entity\NiveauTechnicien;
use App\Entity\Enseigne;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    private $container;

    /**
     * UserType constructor.
     * @param $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['data'];
        $builder->add('nom', TextType::class, [
            'label' => 'Nom',
            'attr' => [
                'autofocus' => ''
            ]
        ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom'
                ])
                ->add('emailPro', EmailType::class, [
                    'label' => 'Email pro'
                ])
                ->add('emailPerso', EmailType::class, [
                    'label' => 'Email perso',
                    'required' => false,
                ])
                ->add('telephone', TelType::class, [
                    'label' => 'Téléphone',
                    'required' => false
                ])
        ;
        if (in_array($this->container->getParameter('ROLE_TECHNICIEN'), $user->getRoles())) {
            $builder->add('niveauTechnicien', EntityType::class, [
                'label' => 'Niveau technicien',
                'class' => NiveauTechnicien::class,
                'choice_label' => function(NiveauTechnicien $niveauTechnicien) {
                    return $niveauTechnicien->getLibelle() . ' - ' . $niveauTechnicien->getTarifHoraire() . " €/heure";
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucun niveau sélectionné'
                ],
                'required' => true,
            ]);
        }
        if (in_array($this->container->getParameter('ROLE_CLIENT'), $user->getRoles())) {
            $builder->add('enseignes', EntityType::class, [
                'label' => 'Enseignes',
                'class' => Enseigne::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'data-size' => 10,
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucune enseigne sélectionnée'
                ],
                'multiple' => true
            ]);
        }
        $builder->add('isActive', CheckboxType::class, [
            'label' => 'Compte actif',
            'label_attr' => [
                'class' => 'checkbox-custom'
            ],
            'required' => false,
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

}
