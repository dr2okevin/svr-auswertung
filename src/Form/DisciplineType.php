<?php

namespace App\Form;

use App\Entity\Discipline;
use App\Enum\ScoringMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisciplineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Name', null, [
                'label' => 'Name',
            ])
            ->add('ShotsPerSeries', null, [
                'label' => 'Schüsse je Serie',
            ])
            ->add('ScoringMode', EnumType::class, [
                'class' => ScoringMode::class,
                'label' => 'Wertungsmodus',
                'choice_label' => static fn (ScoringMode $mode): string => $mode->getLabel(),
            ])
            ->add('MaxScoresPerShot', null, [
                'label' => 'Maximale Ringe je Schuss',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discipline::class,
        ]);
    }
}
