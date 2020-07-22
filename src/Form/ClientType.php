<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nom', TextType::class, [
                'label' => 'Nom du client'
            ])
                ->add('adresseSiegeSociale', TextareaType::class, [
                    'label' => 'Adresse du siège social',
                    'attr' => [
                        'rows' => 4
                    ]
                ])
                ->add('numSiret', TextType::class, [
                    'label' => 'SIRET'
                ])
                ->add('telephone', TextType::class, [
                    'label' => 'Téléphone'
                ])
                ->add('mailsRetoursBouteilles', TextType::class, [
                    'label' => 'Mails de retours bouteilles',
                    'help' => 'Séparées par des virgules "," sans espace'
                ])
            ->add('entete', FileType::class, [
                'label' => 'Image entête',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1M',
                        'mimeTypes' => [
                            'image/*'
                        ],
                        'mimeTypesMessage' => 'Importer un fichier valide (.jpeg, .png or .svg...)'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
