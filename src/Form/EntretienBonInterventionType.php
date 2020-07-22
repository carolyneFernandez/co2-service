<?php

namespace App\Form;

use App\Entity\EntretienBonIntervention;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntretienBonInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            'CT' => 'CT',
            'SM' => 'SM',
            'T' => 'T',
        ];

        $builder->add('client', TextType::class, [
            'label' => 'Client',
        ])
                ->add('adresse', TextareaType::class, [
                    'label' => 'Adresse',
                    'attr' => [
                        'rows' => 3,
                    ],
                ])
                ->add('codePostal', TextType::class, [
                    'label' => 'Code postal',
                ])
                ->add('ville', TextType::class, [
                    'label' => 'Ville',
                ])
                ->add('date', DateType::class, [
                    'label' => 'Date',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'flatpickr-date'
                    ],
                ])
                ->add('telephone', TelType::class, [
                    'label' => 'Tel.',
                    'required' => false,

                ])
                ->add('numeroContrat', TextType::class, [
                    'label' => 'Contrat d\'entretien N°',
                    'required' => false,
                ])
                ->add('operationsEffectuees', TextareaType::class, [
                    'label' => 'Opérations effectuées sur : ',
                    'attr' => [
                        'rows' => 6,
                    ],
                    'required' => false,
                ])
                ->add('TE_Verif_temp', ChoiceType::class, [
                    'label' => 'Vérification des températures et du givre des évaporateurs',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_charge_fluide', ChoiceType::class, [
                    'label' => 'Contrôle de la charge en fluide frigorigène',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Verif_fonct_groupes', ChoiceType::class, [
                    'label' => 'Vérification du fonctionnement des groupes',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_niv_huile_comp', ChoiceType::class, [
                    'label' => 'Contrôle du niveau d\'huile dans les compresseurs',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Verif_regl_pend_deri', ChoiceType::class, [
                    'label' => 'Vérification, réglage des pendules de dégivrage',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Verif_electrique', ChoiceType::class, [
                    'label' => 'Vérification électrique (fusible, connectique)',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_condens', ChoiceType::class, [
                    'label' => 'Nettoyage des condenseurs à l\'azote ou à l\'eau sous pression',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_moteurs_elec', ChoiceType::class, [
                    'label' => 'Nettoyage et graissage des moteurs électriques',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_etanch_circ_frig', ChoiceType::class, [
                    'label' => 'Contrôle d\'étanchéité des circuits frigorifiques',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_bacs_condens', ChoiceType::class, [
                    'label' => 'Nettoyage des bacs de relevage des eaux de condensat',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_salle_machine', ChoiceType::class, [
                    'label' => 'Nettoyage salle des machines et grille d\'amenée d\'air',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_evapo', ChoiceType::class, [
                    'label' => 'Nettoyage des évaporateurs des chambres froides',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_Nett_gaine_air', ChoiceType::class, [
                    'label' => 'Contrôle et nettoyage des gaines d\'air des vitrines',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Nett_evap_egout', ChoiceType::class, [
                    'label' => 'Nettoyage des évaporateurs et égouttoirs des vitrines',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_venti_cham', ChoiceType::class, [
                    'label' => 'Contrôle des ventilateurs des vitrines et chambres',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_Nett_ecoul_degiv', ChoiceType::class, [
                    'label' => 'Contrôle et nettoyage des écoulements de dégivrage',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Ctrl_tension_courroies', ChoiceType::class, [
                    'label' => 'Contrôle de tension et d\'usure des courroies',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('TE_Maj_cahier_fluide', ChoiceType::class, [
                    'label' => 'Mise à jour du cahier des fluides',
                    'choices' => $choices,
                    'label_attr' => [
                        'class' => 'checkbox-custom'
                    ],
                    'choice_label' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('rapportTechnicien', TextareaType::class, [
                    'label' => 'Rapport du technicien',
                    'attr' => [
                        'rows' => 6,
                    ],
                ])
                ->add('observationClient', TextareaType::class, [
                    'label' => 'Observation du client',
                    'attr' => [
                        'rows' => 6,
                    ],
                    'required' => false,
                ])
                ->add('signatureClient', TextareaType::class, [
                    'label' => 'Signature du client',
                    'attr' => [
                        'rows' => 6,
                    ],
                    'required' => false,
                ])
                ->add('nomSignataireClient', TextType::class, [
                    'label' => 'Nom du signataire',
                    'required' => false,
                ])
                ->add('signatureTechnicien', TextareaType::class, [
                    'label' => 'Signature du technicien',
                    'attr' => [
                        'rows' => 6,
                    ],
                    'required' => false,
                ])
                ->add('entretienBonInterventionFournitures', CollectionType::class, [
                    'label' => 'Fournitures',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options' => array('label' => false),
                    'delete_empty' => true,
                    'entry_type' => EntretienBonInterventionFournitureType::class,
                    'prototype' => true,
                    'by_reference' => false,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EntretienBonIntervention::class,
        ]);
    }

}
