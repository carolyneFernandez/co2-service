<?php

namespace App\Form\Factures\Depannage;

use App\Data\FiltreFactureDepannageData;
use App\Data\FiltreDepannageAFacturerData;
use App\Entity\Factures\FactureDepannage;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class FiltreFactureDepannageDataType extends AbstractType
{
    private $container;
    private $security;

    /**
     * FiltreDepannageDataType constructor.
     * @param $security
     * @param $container
     */
    public function __construct(Security $security, ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('numero', TextType::class, [
            'label' => 'N°',
            'required' => false,
            'attr' => [
                'placeholder' => 'Numéro de facture de CO2Services'
            ]
        ])
                ->add('numeroFactureClient', TextType::class, [
                    'label' => 'Numéro de facture client',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Numéro de facture du client'
                    ]

                ])
                ->add('isPaye', ChoiceType::class, [
                    'label' => 'Payée ?',
                    'choices' => [
                        'Payées' => '1',
                        'Impayées' => '0',
                    ],
                    'required' => false,
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-none-selected-text' => 'Toutes',
                        'data-style' => 'form-control',
                    ],
                    'placeholder' => 'Toutes',
                ])
        ;

        if ($this->security->isGranted($this->container->getParameter('ROLE_ADMIN'))) {

            $builder->add('chargeAffaires', EntityType::class, [
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
                'attr' => [
                    'class' => 'selectpicker',
                    'data-none-selected-text' => 'Tous',
                    'data-live-search' => true,
                    'data-style' => 'form-control',
                    'data-size' => 10,
                ],
                'multiple' => true,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FiltreFactureDepannageData::class,
            'method' => 'GET',
            'csrf_protection' => false,

        ]);
    }

}
