<?php

namespace App\Form;

use App\Entity\DepannageHoraireTechnicien;
use App\Entity\Enseigne;
use App\Entity\Livraison;
use App\Entity\User;
use App\Entity\VilleLivraison;
use App\Repository\UserRepository;
use Detection\MobileDetect;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LivraisonFinType extends AbstractType
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

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('Europe/Paris'));

        $dateDebut = $livraison->getDateReleve();

        $detect = new MobileDetect();
        if (!$detect->isMobile() && !$detect->isTablet()) {
            $dateDebut->setTimezone(new \DateTimeZone('Europe/Paris'));
        }

        $builder->add('dateLivraison', DateTimeType::class, [
            'label' => 'Date de la livraison',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'data' => $now,
            'attr' => [
                'class' => 'flatpickr-datetime',
                'data-min_date' => $dateDebut->format('Y-m-d\\TH:i:s'),
                'data-max_date' => $now->format('Y-m-d\\TH:i:s'),

            ],
        ])
                ->add('submit', SubmitType::class, [
                    'label' => 'Enregistrer',
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ],
                    'row_attr' => [
                        'class' => 'text-center'
                    ],
                ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
            'statut' => "",
        ]);
    }

}
