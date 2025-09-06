<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExportSelectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('globalObjectId', TextType::class, [
                'label' => 'Global Object ID',
                'required' => false,
                'help' => 'Enter a specific Global Object ID to export only that entity',
                'attr' => [
                    'placeholder' => 'e.g., 12345',
                    'class' => 'form-control'
                ]
            ])
            ->add('tagId', ChoiceType::class, [
                'label' => 'Filter by Tag',
                'required' => false,
                'placeholder' => 'Select a tag (optional)',
                'choices' => $options['tag_choices'],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('objectType', ChoiceType::class, [
                'label' => 'Object Type',
                'required' => false,
                'placeholder' => 'Select object type (optional)',
                'choices' => $options['object_type_choices'],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('exportFormat', ChoiceType::class, [
                'label' => 'Export Format',
                'required' => true,
                'choices' => [
                    'CSV' => 'csv',
                    'JSON' => 'json',
                    'XML' => 'xml'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Export Data',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tag_choices' => [],
            'object_type_choices' => [],
        ]);

        $resolver->setAllowedTypes('tag_choices', 'array');
        $resolver->setAllowedTypes('object_type_choices', 'array');
    }
}
