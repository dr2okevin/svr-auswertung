<?php

namespace App\Form;

use App\Entity\Shot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ShotIndex', IntegerType::class, [
                'label' => 'Schussnummer',
            ])
            ->add('value', NumberType::class, [
                'label' => 'Wert',
                'scale' => 1,
            ])
            ->add('XPosition', NumberType::class, [
                'label' => 'X-Position',
                'required' => false,
                'scale' => 4,
            ])
            ->add('YPosition', NumberType::class, [
                'label' => 'Y-Position',
                'required' => false,
                'scale' => 4,
            ])
            ->add('RecordTime', DateTimeType::class, [
                'label' => 'Aufnahmezeit',
                'widget' => 'single_text',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shot::class,
        ]);
    }
}
