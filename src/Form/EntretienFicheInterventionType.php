<?php

namespace App\Form;

use App\Entity\EntretienFicheIntervention;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntretienFicheInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntretienFicheIntervention $entretienFicheIntervention */
        $entretienFicheIntervention = $options['data'];

        $classNInterAutre = '';
        if ($entretienFicheIntervention->getNIntervention() != 'Autre (préciser)')
            $classNInterAutre = 'd-none';

        $Denom_ADR_RID_autreClass = '';
        if ($entretienFicheIntervention->getDenomADRRIDAutre() != 'Autre cas')
            $Denom_ADR_RID_autreClass = 'd-none';

//        $detendeur = nl2br($entretienFicheIntervention->getDetendeur());

        $builder->add('numero', TextType::class, [
            'label' => 'Fiche N°',
        ])
                ->add('detendeur', TextareaType::class, [
                    'label' => 'DETENDEUR (Nom, adresse et SIRET)',
                    'attr' => [
                        'rows' => 4
                    ],
                    //                    'data' => $detendeur,
                    'required' => false,
                ])
                ->add('EC_Identification', TextareaType::class, [
                    'label' => 'Identification',
                    'required' => false,
                ])
                ->add('EC_Nature_fluide', TextType::class, [
                    'label' => 'Nature du fluide firgorigène',
                    'attr' => [
                        'prepend' => 'R.',
                    ],
                    'required' => false,
                ])
                ->add('EC_Charge_totale', IntegerType::class, [
                    'label' => 'Charge Totale',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                    ],
                    'required' => false,
                ])
                ->add('EC_Tonnage_eq', TextType::class, [
                    'label' => 'Tonnage équivalent CO2 (HFC/PFC)',
                    'attr' => [
                        'append' => 'Teq CO<sub>2</sub>',
                    ],
                    'required' => false,
                ])
                ->add('N_intervention', ChoiceType::class, [
                    'label' => 'Nature de l\'intervention',
                    'choices' => [
                        'Assemblage de l\'équipement' => 'Assemblage de l\'équipement',
                        'Mise en service de l\'équipement' => 'Mise en service de l\'équipement',
                        'Modification de l\'équipement' => 'Modification de l\'équipement',
                        'Maintenance de l\'équipement' => 'Maintenance de l\'équipement',
                        'Contrôle d\'étanchéité périodique' => 'Contrôle d\'étanchéité périodique',
                        'Contrôle d\'étanchéité non périodique' => 'Contrôle d\'étanchéité non périodique',
                        'Démantèlement' => 'Démantèlement',
                        'Autre (préciser)' => 'Autre (préciser)',
                    ],
                    'placeholder' => 'Non renseignée',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Aucune nature d\'intervention sélectionnée'

                    ],
                    'required' => false,
                ])
                ->add('N_observations', TextareaType::class, [
                    'label' => 'Observations',
                    'attr' => [
                        'rows' => 3
                    ],
                    'required' => false,
                ])
                ->add('N_intervention_autre', TextType::class, [
                    'label' => 'Préciser',
                    'row_attr' => [
                        'class' => $classNInterAutre . ' natureInterAutre-div',
                    ],
                    'required' => false,
                ])
                ->add('CE_Identification', TextType::class, [
                    'label' => 'Identification',
                    'required' => false,
                ])
                ->add('CE_date_controle', DateType::class, [
                    'label' => 'Contrôle le',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'flatpickr-date'
                    ],
                    'required' => false,
                ])
                ->add('CE_sys_det_fuite', ChoiceType::class, [
                    'label' => 'Présence d’un système de détection des fuites',
                    'placeholder' => 'Non renseigné',
                    'choices' => [
                        'Oui' => '1',
                        'Non' => '0'
                    ],
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseigné'
                    ],
                    'required' => false,
                ])
                ->add('QF_HCFC', ChoiceType::class, [
                    'label' => 'HCFC',
                    'choices' => [
                        '2 kg ≤ Q < 30 kg' => '2 kg ≤ Q < 30 kg',
                        '30 kg ≤ Q < 300 kg' => '30 kg ≤ Q < 300 kg',
                        'Q ≥ 300 kg' => 'Q ≥ 300 kg',
                    ],
                    'placeholder' => 'Non renseignée',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseignée',
                    ],
                    'required' => false,
                ])
                ->add('HFC_PFC', ChoiceType::class, [
                    'label' => 'HFC/PFC',
                    'choices' => [
                        '5 t ≤ teqCO2 < 50 t' => '5 t ≤ teqCO2 < 50 t',
                        '50 t ≤ teqCO2 < 500 t' => '50 t ≤ teqCO2 < 500 t',
                        'teqCO2 ≥ 500 t',
                    ],
                    'placeholder' => 'Non renseignée',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseignée',
                    ],
                    'required' => false,
                ])
                ->add('EQ_ss_detec_fuite', ChoiceType::class, [
                    'label' => 'Équip. HCFC et équip. HFC sans système de détection de fuite',
                    'choices' => [
                        '12 mois' => '12 mois',
                        '6 mois' => '6 mois',
                        '3 mois' => '3 mois',
                    ],
                    'placeholder' => 'Non renseigné',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseignée',
                    ],
                    'required' => false,
                ])
                ->add('EQ_ac_detec_fuite', ChoiceType::class, [
                    'label' => 'Équipements HFC avec système de détection des fuites',
                    'choices' => [
                        '24 mois' => '24 mois',
                        '12 mois' => '12 mois',
                        '6 mois' => '6 mois',
                    ],
                    'placeholder' => 'Non renseigné',
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseignée',
                    ],
                    'required' => false,
                ])
                ->add('fuitesConstatees', ChoiceType::class, [
                    'label' => 'Fuites constatées lors du contrôle d’étanchéité',
                    'placeholder' => 'Non renseigné',
                    'choices' => [
                        'Oui' => '1',
                        'Non' => '0'
                    ],
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                        'data-none-selected-text' => 'Non renseigné'
                    ],
                    'required' => false,
                ])
                ->add('entretienFicheInterventionFuites', CollectionType::class, [
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options' => array('label' => false),
                    'delete_empty' => true,
                    'entry_type' => EntretienFicheInterventionFuiteType::class,
                    'prototype' => true,
                    'by_reference' => false,
                ])
                ->add('MFF_QT_Charge_tot', IntegerType::class, [
                    'label' => 'Quantité chargée totale (A+B+C)',
                    'attr' => [
                        'append' => 'kg',
                        'placeholder' => 'automatique',
                        'step' => 'any',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Charge_A', IntegerType::class, [
                    'label' => 'A - Dont fluide vierge',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                        'class' => 'sum-charge-tot',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Charge_B', IntegerType::class, [
                    'label' => 'B - Dont fluide recyclé (Incl. fluide récupéré et réintroduit)',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                        'class' => 'sum-charge-tot',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Charge_C', IntegerType::class, [
                    'label' => 'C - Dont fluide rénégéré',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                        'class' => 'sum-charge-tot',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Recup_tot', IntegerType::class, [
                    'label' => 'Quantité de fluide récupérée totale (D+E)',
                    'attr' => [
                        'append' => 'kg',
                        'placeholder' => 'automatique',
                        'step' => 'any',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Recup_D', IntegerType::class, [
                    'label' => 'D - Dont fluide destiné au traitement',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                        'class' => 'sum-recup-tot',
                    ],
                    'required' => false,
                ])
                ->add('MFF_QT_Recup_E', IntegerType::class, [
                    'label' => 'E - Dont fluide conservé pour réutilisation (Incl. réintroduction)',
                    'attr' => [
                        'append' => 'kg',
                        'step' => 'any',
                        'class' => 'sum-recup-tot',
                    ],
                    'required' => false,
                ])
                ->add('MFF_Identifiant_contenant', TextType::class, [
                    'label' => 'Identifiant du contenant',
                    'required' => false,
                ])
                ->add('Denom_ADR_RID', ChoiceType::class, [
                    'label' => 'Dénomination ADR/RID',
                    'choices' => [
                        'UN 1078, Gaz frigorifique NSA (Gaz réfrigérant, NSA), 2.2 (C/E)' => 'UN 1078, Gaz frigorifique NSA (Gaz réfrigérant, NSA), 2.2 (C/E)',
                        'Autre cas' => 'Autre cas',
                    ],
                    'attr' => [
                        'class' => 'selectpicker',
                        'data-style' => 'form-control',
                    ],
                    'placeholder' => 'Non renseigné',
                    'required' => false,
                ])
                ->add('Denom_ADR_RID_autre', TextType::class, [
                    'label' => 'Préciser',
                    'row_attr' => [
                        'class' => $Denom_ADR_RID_autreClass . ' Denom_ADR_RID_autre',

                    ],
                    'required' => false,
                ])
                ->add('Install_dest_dechets', TextareaType::class, [
                    'label' => 'Installation de destination du déchets (Nom, SIRET et adresse)',
                    'attr' => [
                        'rows' => 3
                    ],
                    'required' => false,
                ])
                ->add('Trans_dechets', TextareaType::class, [
                    'label' => 'Transporteur du déchet - si différent de l’opérateur (Nom, SIREN et adresse)',
                    'attr' => [
                        'rows' => 3
                    ],
                    'required' => false,
                ])
                ->add('Obs_num_bord_collect', IntegerType::class, [
                    'label' => 'N° de bordereau de collecte de petites quantités',
                    'required' => false,
                ])
                ->add('Obs_num_bord_transf', IntegerType::class, [
                    'label' => 'N° de bordereau de transformation traitement',
                    'required' => false,
                ])
                ->add('Install_traitement', TextType::class, [
                    'label' => 'Installation de traitement (nom et adresse)',
                    'required' => false,
                ])
                ->add('Install_trait_code', TextType::class, [
                    'label' => 'Code R/D',
                    'required' => false,
                ])
                ->add('Install_trait_qt_rec', IntegerType::class, [
                    'label' => 'Quantité réceptionnée',
                    'required' => false,
                    'attr' => [
                        'step' => 'any',
                    ]
                ])
                ->add('SIGN_Ope_nom', TextType::class, [
                    'label' => 'Nom du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Ope_qual', TextType::class, [
                    'label' => 'Qualité du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Ope_date', DateType::class, [
                    'label' => 'Date',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'flatpickr-date'
                    ],
                    'required' => false,
                ])
                ->add('SIGN_Ope_visa', TextareaType::class, [
                    'label' => 'Signature',
                    'required' => false,
                ])
                ->add('SIGN_Det_nom', TextType::class, [
                    'label' => 'Nom du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Det_qual', TextType::class, [
                    'label' => 'Qualité du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Det_date', DateType::class, [
                    'label' => 'Date',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'flatpickr-date'
                    ],
                    'required' => false,
                ])
                ->add('SIGN_Det_visa', TextareaType::class, [
                    'label' => 'Signature',
                    'required' => false,
                ])
                ->add('SIGN_Inst_nom', TextType::class, [
                    'label' => 'Nom du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Inst_qual', TextType::class, [
                    'label' => 'Qualité du Signataire',
                    'required' => false,
                ])
                ->add('SIGN_Inst_date', DateType::class, [
                    'label' => 'Date',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'flatpickr-date'
                    ],
                    'required' => false,
                ])
                ->add('SIGN_Inst_visa', TextareaType::class, [
                    'label' => 'Signature',
                    'required' => false,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EntretienFicheIntervention::class,
        ]);
    }

}
