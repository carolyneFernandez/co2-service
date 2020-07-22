<?php

namespace App\Form;

use App\Entity\Enseigne;
use App\Entity\Travaux;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Date;

class TravauxType extends AbstractType
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

        if ($this->security->isGranted($this->container->getParameter('ROLE_ADMIN')) || $this->security->isGranted($this->container->getParameter('ROLE_TECHNICIEN'))) {
            $enseignes = $this->container->get('doctrine')
                                         ->getRepository(Enseigne::class)
                                         ->findBy([], ['nom' => 'ASC'])
            ;
        } else {
            /** @var User $user */
            $user = $this->security->getUser();
            $enseignes = $user->getEnseignes();
        }


        /** @var Travaux $travaux */
        $travaux = $options['data'];
        if ($this->security->isGranted('ROLE_ADMIN')) {

            $builder->add('chargeAffaire', EntityType::class, [
                'label' => "Chargé d'affaire",
                'class' => User::class,
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                              ->where('u.roles like :role')
                              ->setParameter('role', '%' . $this->container->getParameter('ROLE_CLIENT') . '%')
                        ;
                },
                'choice_label' => function(User $user) {
                    return $user->getNom() . " " . $user->getPrenom();
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'data-size' => 10,
                    'data-style' => 'form-control',
                    'data-none-selected-text' => 'Aucun chargé d\'affaires sélectionné'
                ],
            ]);
        }

        $builder->add('enseigne', EntityType::class, [
            'label' => 'Enseigne',
            'class' => Enseigne::class,
            'required' => true,
            'choices' => $enseignes,
            'attr' => [
                'class' => 'selectpicker',
                'data-live-search' => true,
                'data-size' => 10,
                'data-style' => 'form-control',
                'data-none-selected-text' => 'Aucune sélectionnée'
            ],
            'choice_label' => 'nom',
        ])
                ->add('ville', TextType::class, [
                    'label' => 'Ville'
                ])
                ->add('departement', IntegerType::class, [
                    'label' => 'Département'
                ])
                ->add('adresse', TextareaType::class, [
                    'label' => 'Adresse complète',
                    'attr' => [
                        'rows' => 3
                    ],
                ])
                ->add('typeIntervention', TextType::class, [
                    'label' => "Type d'intervention"
                ])
                ->add('reference', TextType::class, [
                    'label' => 'Référence'
                ])
                ->add('nombreTechNecessaire', IntegerType::class, [
                    'label' => 'Nombre de techniciens nécessaires'
                ])
                ->add('nombreJourNecessaire', IntegerType::class, [
                    'label' => 'Nombre de jours nécessaires'
                ])
                ->add('dateDebutSouhaitee', DateType::class, [
                    'label' => 'Date de début souhaitée',
                    'widget' => 'single_text',
                    'view_timezone' => 'Europe/Paris',
                    'attr' => [
                        "class" => "flatpickr-date"
                    ]
                ])
                ->add('commentaires', TextareaType::class, [
                    'label' => 'Commentaires',
                    'attr' => [
                        'rows' => 3
                    ],
                    'required' => false,
                ])
                ->add('suiviTravaux', TextareaType::class, [
                    'label' => 'Suivi de travaux',
                    'required' => false
                ])
        ;


        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('dateDebutRetenue', DateType::class, [
                'label' => 'Date de début retenue',
                'widget' => 'single_text',
                'required' => false,
                'view_timezone' => 'Europe/Paris',
                'attr' => [
                    "class" => "flatpickr-date"
                ]
            ])
                    ->add('techniciens', EntityType::class, [
                        'label' => 'Techniciens',
                        'class' => User::class,
                        'multiple' => true,
                        'required' => false,
                        'attr' => [
                            'class' => 'selectpicker',
                            'data-live-search' => true,
                            'data-size' => 10,
                            'data-style' => 'form-control',
                            'data-none-selected-text' => 'Aucun technicien sélectionné'
                        ],
                        'query_builder' => function(EntityRepository $er) {
                            return $er->createQueryBuilder('u')
                                      ->where('u.roles like :role')
                                      ->setParameter('role', '%' . $this->container->getParameter('ROLE_TECHNICIEN') . '%')
                                ;
                        },
                        'choice_label' => function(User $user) {
                            if ($user->getNiveauTechnicien()) {
                                return $user->getNom() . " " . $user->getPrenom() . " - " . $user->getNiveauTechnicien()
                                                                                                 ->getLibelle()
                                    ;
                            }

                            return $user->getNom() . " " . $user->getPrenom();
                        }
                    ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Travaux::class,
        ]);
    }

}
