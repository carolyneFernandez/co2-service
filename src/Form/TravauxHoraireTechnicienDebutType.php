<?php

namespace App\Form;

use App\Entity\Travaux;
use App\Entity\TravauxHoraireTechnicien;
use App\Entity\User;
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
use Symfony\Component\Validator\Constraints\File;


class TravauxHoraireTechnicienDebutType extends AbstractType
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
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('Europe/Paris'));
//        $now->modify("+1 minutes");
//        dump($now);

        $builder->add('dateDebut', DateTimeType::class, [
            'label' => 'Date de début',
            'widget' => 'single_text',
            'view_timezone' => 'Europe/Paris',
            'attr' => [
                'class' => 'flatpickr-datetime',
                'data-max_date' => $now->format("Y'-'m'-'d'T'H':'i"),

            ],

        ])
                ->add('submit', SubmitType::class, [
                    'label' => 'Début de l\'horaire',
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
            'data_class' => TravauxHoraireTechnicien::class,
        ]);
    }

}
