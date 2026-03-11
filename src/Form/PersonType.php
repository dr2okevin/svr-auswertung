<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('FristName', null, [
                'label' => 'Vorname',
            ])
            ->add('LastName', null, [
                'label' => 'Nachname',
            ])
            ->add('Birthdate', DateType::class, [
                'label' => 'Geburtsdatum',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'html5' => true,
            ])
            ->add('Professional', null, [
                'label' => 'Profi',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
