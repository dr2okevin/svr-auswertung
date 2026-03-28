<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShotBatchRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ShotIndex', IntegerType::class, [
                'label' => '#',
                'disabled' => true,
            ])
            ->add('value', NumberType::class, [
                'label' => 'Wert',
                'required' => false,
                'scale' => 1,
            ])
            ->add('XPosition', NumberType::class, [
                'label' => 'X',
                'required' => false,
                'scale' => 4,
            ])
            ->add('YPosition', NumberType::class, [
                'label' => 'Y',
                'required' => false,
                'scale' => 4,
            ])
            ->add('RecordTime', DateTimeType::class, [
                'label' => 'Aufnahmezeit',
                'required' => false,
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
