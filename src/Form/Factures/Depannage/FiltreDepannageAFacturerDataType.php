<?php

namespace App\Form\Factures\Depannage;

use App\Data\FiltreDepannageAFacturerData;
use App\Entity\Factures\FactureDepannage;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class FiltreDepannageAFacturerDataType extends AbstractType
{
    private $container;

    /**
     * FiltreDepannageDataType constructor.
     * @param $security
     * @param $container
     */
    public function __construct(Security $security, ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('dateDebutPeriode', DateType::class, [
            'label' => 'Début de la période à sélectionner',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'attr' => [
                'class' => 'flatpickr-datetime flatpicker-datedebut',
                'data-datefin' => '.flatpicker-datefin',
            ],
            'required' => false,
        ])
                ->add('dateFinPeriode', DateType::class, [
                    'label' => 'Fin de la période à sélectionner',
                    'widget' => 'single_text',
                    'view_timezone' => 'Europe/Paris',
                    'attr' => [
                        'class' => 'flatpickr-datetime flatpicker-datefin',
                        'data-datedebut' => '.flatpicker-datedebut',
                    ],
                    'required' => false,
                ])
                ->add('chargeAffaires', EntityType::class, [
                    'label' => 'Chargés d\'affaires',
                    'class' => User::class,
                    'choice_label' => function(User $entity) {
                        return $entity->getNom() . ' ' . $entity->getPrenom();
                    },
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('ca')
                                  ->where('ca.roles like :role')
                                  ->setParameter('role', '%' . $this->container->getParameter('ROLE_CLIENT') . '%')
                            ;
                    },
                    'placeholder' => 'Tous',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-none-selected-text' => 'Tous',
                        'data-live-search' => true,
                        'data-style' => 'form-control',
                        'data-size' => 10,
                    ],
                    'multiple' => true,
                    'required' => false,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FiltreDepannageAFacturerData::class,
            'method' => 'GET',
            'csrf_protection' => false,

        ]);
    }

}
