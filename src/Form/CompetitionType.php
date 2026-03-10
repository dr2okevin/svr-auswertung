<?php

namespace App\Form;

use App\Entity\Competition;
use App\Enum\CompetitionType as CompetitionTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompetitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Name', null, [
                'label' => 'Name',
            ])
            ->add('Type', EnumType::class, [
                'class' => CompetitionTypeEnum::class,
                'label' => 'Typ',
                'choice_label' => static fn (CompetitionTypeEnum $type): string => $type->getLabel(),
            ])
            ->add('StartTime', DateTimeType::class, [
                'label' => 'Startzeit',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
            ])
            ->add('EndTime', DateTimeType::class, [
                'label' => 'Endzeit',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competition::class,
        ]);
    }
}
