<?php

namespace App\Form;

use App\Entity\Enseigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemPaginationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('maxItemPerPage', ChoiceType::class, [
            'label' => 'Nombre par page',
            'choices' => [
                '10' => '10',
                '20' => '20',
                '30' => '30',
                '40' => '40',
                '50' => '50',
                '60' => '60',
                '70' => '70',
                '80' => '80',
                '90' => '90',
                '100' => '100',
            ],
            'attr' => [
                'class' => 'selectpicker selectItemPerPage',
                'data-width' => 'fit',
            ],
            'label_attr' => [
                'class' => 'no-star-required'
            ],
            'row_attr' => [
                'class' => 'mb-0'
            ]
        ]);
    }

}
