<?php

namespace App\Form;

use App\Entity\Entretien;
use App\Entity\EntretienHoraireTechnicien;
use App\Entity\User;
use Detection\MobileDetect;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\File;


class EntretienHoraireTechnicienFinType extends AbstractType
{

    private $security;
    private $container;

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
        /** @var EntretienHoraireTechnicien $horaire */
        $horaire = $options['data'];
        if (!$horaire->getDateFin()) {
            $date = new \DateTime();
        } else {
            $date = $horaire->getDateFin();
        }

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('Europe/Paris'));

        $dateDebut = $horaire->getDateDebut();

        $detect = new MobileDetect();
        if (!$detect->isMobile() && !$detect->isTablet()) {
            $dateDebut->setTimezone(new \DateTimeZone('Europe/Paris'));
        }

        $builder->add('dateFin', DateTimeType::class, [
            'label' => 'Date de fin',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'attr' => [
                'class' => 'flatpickr-datetime',
                'data-min_date' => $dateDebut->format('Y-m-d\\TH:i:s'),
                'data-max-date' => $now->format('Y-m-d\\TH:i:s'),
            ],
            'data' => $date
        ])
                ->add('submit', SubmitType::class, [
                    'label' => 'Fin de l\'horaire',
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ],
                    'row_attr' => [
                        'class' => 'text-center'
                    ]

                ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EntretienHoraireTechnicien::class,
        ]);
    }

}
