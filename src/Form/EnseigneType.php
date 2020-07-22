<?php

namespace App\Form;

use App\Entity\Enseigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnseigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nom', TextType::class, [
            'label' => 'Nom de l\'enseigne',
            'attr' => [
                'autofocus' => ''
            ],
        ])
                ->add('adresse', TextareaType::class, [
                    'label' => 'Adresse de l\'enseigne',
                    'required' => false,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Enseigne::class,
        ]);
    }

}
