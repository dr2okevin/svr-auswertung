<?php

namespace App\Form;

use App\Entity\Team;
use App\Enum\TeamType as TeamTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Name', null, [
                'label' => 'Name',
            ])
            ->add('Type', EnumType::class, [
                'class' => TeamTypeEnum::class,
                'label' => 'Typ',
                'choice_label' => static fn (TeamTypeEnum $type): string => $type->getLabel(),
            ])
            ->add('TeamMembers', CollectionType::class, [
                'label' => 'Teammitglieder',
                'entry_type' => TeamMemberType::class,
                'entry_options' => [
                    'disciplines' => $options['disciplines'],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
            'disciplines' => [],
        ]);

        $resolver->setAllowedTypes('disciplines', 'array');
    }
}
