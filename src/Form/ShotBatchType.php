<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShotBatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('shots', CollectionType::class, [
            'entry_type' => ShotBatchRowType::class,
            'label' => false,
            'allow_add' => false,
            'allow_delete' => false,
            'by_reference' => false,
        ])
            ->add('finalScoreOverride', NumberType::class, [
                'label' => 'Endergebnis überschreiben',
                'required' => false,
                'scale' => 1,
                'empty_data' => '',
                'help' => 'Optional: Wird dieser Wert gesetzt, zählt er statt der Summe aller Schüsse.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
