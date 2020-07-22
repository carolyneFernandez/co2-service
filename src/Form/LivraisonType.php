<?php

namespace App\Form;

use App\Entity\Enseigne;
use App\Entity\Livraison;
use App\Entity\TarifLivraison;
use App\Entity\User;
use App\Entity\VilleLivraison;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LivraisonType extends AbstractType
{
    private $security;
    private $userRepository;
    private $container;

    public function __construct(Security $security, UserRepository $userRepository, ContainerInterface $container)
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Livraison $livraison */
        $livraison = $options['data'];
        $idLivraison = $livraison->getId();
        $adresseAuto = $idLivraison ? 0 : 1;

        /** @var User $user */
        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $enseignes = $this->container->get('doctrine')
                                         ->getRepository(Enseigne::class)
                                         ->findBy([], [
                                             'nom' => 'ASC'
                                         ])
            ;
        } else {
            $enseignes = $user->getEnseignes();
        }

        $em = $this->container->get('doctrine')
                              ->getManager()
        ;

        $villes = [];

        if ($this->security->isGranted($this->container->getParameter('ROLE_CLIENT'))) {
            $villes = $em->getRepository(VilleLivraison::class)
                         ->findBy(['disponible' => true], ['nom' => 'ASC'])
            ;
        } elseif ($this->security->isGranted($this->container->getParameter('ROLE_ADMIN')) && $idLivraison == null) {
            $villes = $em->getRepository(VilleLivraison::class)
                         ->findBy(['disponible' => true], ['nom' => 'ASC'])
            ;

        } elseif ($this->security->isGranted($this->container->getParameter('ROLE_ADMIN')) && $idLivraison != null) {
            $villes = $em->getRepository(VilleLivraison::class)
                         ->findBy([], ['nom' => 'ASC'])
            ;

        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('chargeAffaire', EntityType::class, [
                'label' => 'Charge d\'affaire',
                'class' => User::class,
                'choices' => $this->userRepository->getUsersByRole($this->container->getParameter('ROLE_CLIENT')),
                'choice_label' => function(User $ca) {
                    return $ca->getNom() . ' ' . $ca->getPrenom();
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-none-selected-text' => 'Aucun',
                    'data-live-search' => true,
                    'data-style' => 'form-control',
                    'data-size' => 10,
                ],
            ]);
        }


        $builder->add('dateSouhaitee', DateTimeType::class, [
            'date_label' => 'Date souhaitée',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'attr' => ['class' => 'flatpickr-datetime']
        ])
                ->add('materielTransporte', TextType::class, [
                    'required' => false,
                    'label' => 'Matériel transporté'
                ])
                ->add('reference', TextType::class, [
                    'label' => 'Référence'
                ])
                ->add('commentaires', TextareaType::class, [
                    'label' => 'Commentaires',
                    'attr' => [
                        'rows' => 3
                    ],
                    'required' => false,
                ])
                ->add('villeDepart', EntityType::class, [
                    'label' => 'Département de départ',
                    'class' => VilleLivraison::class,
                    'choice_label' => function(VilleLivraison $villeDepart) {
                        return $villeDepart->getNom();
                    },
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-none-selected-text' => 'Aucun département sélectionné',
                        'data-live-search' => true,
                        'data-style' => 'form-control',
                        'data-size' => 10,
                    ],
                    'choices' => $villes,
                    'choice_attr' => function($choice, $key, $value) use ($em) {
                        $class = '';
                        if (!$em->getRepository(VilleLivraison::class)
                                ->find($value)
                                ->isDisponible()) {
                            $class = 'text-warning';
                        }

                        return ['class' => $class];
                    }
                ])
                ->add('adresseDepart', TextareaType::class, [
                    'label' => 'Adresse de départ',
                    'attr' => [
                        'placeholder' => 'Adresse complète',
                        'class' => 'adresse-field',
                        'data-complete-auto' => $adresseAuto,
                    ],
                ])
                ->add('enseigneDepart', EntityType::class, [
                    'label' => 'Enseigne de départ',
                    'class' => Enseigne::class,
                    'choices' => $enseignes,
                    'choice_label' => function(Enseigne $enseigneDepart) {
                        return $enseigneDepart->getNom();
                    },
                    'choice_attr' => function($choice, $key, $value) use ($em) {
                        $attr = [];
                        $enseigne = $em->getRepository(Enseigne::class)
                                       ->find($value)
                        ;
                        $attr['data-adresse'] = $enseigne->getAdresse();

                        return $attr;
                    },
                    'attr' => [
                        'class' => 'selectpicker enseigne-field',
                        'data-none-selected-text' => 'Aucune enseigne sélectionnée',
                        'data-live-search' => true,
                        'data-style' => 'form-control',
                        'data-size' => 10,
                    ],
                ])
                ->add('villeArrivee', EntityType::class, [
                    'label' => 'Département d\'arrivée',
                    'class' => VilleLivraison::class,
                    'choice_label' => function(VilleLivraison $villeArrive) {
                        return $villeArrive->getNom();
                    },
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-none-selected-text' => 'Aucun département sélectionné',
                        'data-live-search' => true,
                        'data-style' => 'form-control',
                        'data-size' => 10,
                    ],
                    'choices' => $villes,
                    'choice_attr' => function($choice, $key, $value) use ($em) {
                        $class = '';
                        if (!$em->getRepository(VilleLivraison::class)
                                ->find($value)
                                ->isDisponible()) {
                            $class = 'text-warning';
                        }

                        return ['class' => $class];
                    }
                ])
                ->add('adresseArrivee', TextareaType::class, [
                    'label' => 'Adresse d\'arrivée',
                    'attr' => [
                        'placeholder' => 'Adresse complète',
                        'class' => 'adresse-field',
                        'data-complete-auto' => $adresseAuto,
                    ],
                ])
                ->add('enseigneArrivee', EntityType::class, [
                    'label' => 'Enseigne d\'arrivée',
                    'class' => Enseigne::class,
                    'choices' => $enseignes,
                    'choice_label' => function(Enseigne $enseigneArrivee) {
                        return $enseigneArrivee->getNom();
                    },
                    'choice_attr' => function($choice, $key, $value) use ($em) {
                        $attr = [];
                        $enseigne = $em->getRepository(Enseigne::class)
                                       ->find($value)
                        ;
                        $attr['data-adresse'] = $enseigne->getAdresse();

                        return $attr;
                    },
                    'attr' => [
                        'class' => 'selectpicker enseigne-field',
                        'data-none-selected-text' => 'Aucune enseigne sélectionnée',
                        'data-live-search' => true,
                        'data-style' => 'form-control',
                        'data-size' => 10,
                    ],
                ])
        ;
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $now = new \DateTime();
            $builder->add('techniciens', EntityType::class, [
                'label' => 'Techniciens',
                'class' => User::class,
                'choices' => $this->userRepository->getUsersByRole($this->container->getParameter('ROLE_TECHNICIEN')),
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
                    'data-none-selected-text' => 'Aucun',
                    'data-live-search' => true,
                    'data-style' => 'form-control',
                    'data-size' => 10,
                ],
                'multiple' => true,
                'required' => false,
            ])
                    ->add('dateRetenue', DateTimeType::class, [
                        'date_label' => 'Date retenue',
                        'widget' => 'single_text',
                        'view_timezone' => 'Europe/Paris',
                        'empty_data' => $now->format('Y-m-d H:i'),
                        'attr' => ['class' => 'flatpickr-datetime']
                    ])
                    ->add('dateReleve', DateTimeType::class, [
                        'label' => 'Date de la relève',
                        'widget' => 'single_text',
                        'view_timezone' => 'Europe/Paris',
                        'attr' => ['class' => 'flatpickr-datetime'],
                        'required' => false,
                    ])
                    ->add('dateLivraison', DateTimeType::class, [
                        'label' => 'Date de la livraison',
                        'widget' => 'single_text',
                        'view_timezone' => 'Europe/Paris',
                        'attr' => ['class' => 'flatpickr-datetime'],
                        'required' => false,
                    ])
            ;

        }


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
            'statut' => "",
        ]);
    }

}
