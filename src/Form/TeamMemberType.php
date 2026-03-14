<?php

namespace App\Form;

use App\Entity\Discipline;
use App\Entity\Person;
use App\Entity\TeamMember;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Person', EntityType::class, [
                'class' => Person::class,
                'choice_label' => static fn (Person $person): string => sprintf('%s %s', $person->getFristName(), $person->getLastName()),
                'label' => 'Person',
            ])
            ->add('Discipline', EntityType::class, [
                'class' => Discipline::class,
                'choices' => $options['disciplines'],
                'choice_label' => 'Name',
                'label' => 'Disziplin',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TeamMember::class,
            'disciplines' => [],
        ]);

        $resolver->setAllowedTypes('disciplines', 'array');
    }
}
