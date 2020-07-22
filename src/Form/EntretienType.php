<?php

namespace App\Form;

use App\Entity\Enseigne;
use App\Entity\Entretien;
use App\Entity\NiveauTechnicien;
use App\Entity\StatutActivite;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

;


use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntretienType extends AbstractType
{
    private $container;
    private $security;

    /**
     * EvenementType constructor.
     * @param Security $security
     * @param $container
     */
    public function __construct(Security $security, ContainerInterface $container)
    {

        $this->security = $security;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('chargeAffaire', EntityType::class, [
                'class' => User::class,
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                              ->where('u.roles like :role')
                              ->setParameter('role', '%' . $this->container->getParameter('ROLE_CLIENT') . '%')
                        ;
                },
                'choice_label' => 'nom',
                'required' => true,
                //                'multiple' => true,
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'data-size' => 10,
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucun chargé d\'affaires sélectionné'
                ],

            ]);

        }


        $builder->add('dateDebut', DateTimeType::class, [
            'label' => 'Date de début',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'attr' => [
                'class' => 'flatpickr-datetime flatpicker-datedebut',
                'data-datefin' => '.flatpicker-datefin',
            ],
        ])
                ->add('dateFin', DateTimeType::class, [
                    'label' => 'Date de fin',
                    'widget' => 'single_text',
                    'view_timezone' => 'Europe/Paris',
                    'attr' => [
                        'class' => 'flatpickr-datetime flatpicker-datefin',
                        'data-datedebut' => '.flatpicker-datedebut',
                    ],
                ])
                ->add('type', TextType::class, [
                    'label' => 'Type'
                ])
                ->add('numeroContrat', TextType::class, [
                    'label' => 'Numéro de contrat',
                    'required' => false,
                ])
                ->add('codePostal', TextType::class, [
                    'label' => 'Code postal'
                ])
                ->add('ville', TextType::class, [
                    'label' => 'Ville'
                ])
                ->add('adresse', TextareaType::class, [
                    'label' => 'Adresse',
                    'attr' => [
                        'rows' => 3
                    ]
                ])
//                ->add('statut', EntityType::class, [
//                    'class' => StatutActivite::class,
//                    'label' => 'Statut',
//                    'choice_label' => 'libelle',
//                    'required' => true,
//                ])
            ->add('enseigne', EntityType::class, [
                'class' => Enseigne::class,
                'choice_label' => 'nom',
                'required' => true,
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'data-size' => 10,
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucune enseigne sélectionnée'
                ],


            ])
            ->add('commentaires', TextareaType::class, [
                'label' => 'Commentaires',
                'attr' => [
                    'rows' => 3
                ],
                'required' => false,
            ])
        ;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('techniciens', EntityType::class, [
                'class' => User::class,
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                              ->where('u.roles like :role')
                              ->setParameter('role', '%' . $this->container->getParameter('ROLE_TECHNICIEN') . '%')
                        ;
                },
                'required' => false,
                'multiple' => true,
                'choice_label' => function(User $user) {
                    if ($user->getNiveauTechnicien()) {
                        return $user->getNom() . " " . $user->getPrenom() . " - " . $user->getNiveauTechnicien()
                                                                                         ->getLibelle()
                            ;
                    }

                    return $user->getNom() . " " . $user->getPrenom();
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'data-size' => 10,
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucun technicien sélectionné'
                ],

            ]);

        }

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Entretien::class,
        ]);
    }


}
