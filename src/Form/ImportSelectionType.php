<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ImportSelectionType extends AbstractType
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
                'placeholder' => 'Select a tag',
                'choices' => $options['tag_choices'],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('objectType', ChoiceType::class, [
                'label' => 'Object Type',
                'required' => true,
                'placeholder' => 'Select object type',
                'choices' => $options['object_type_choices'],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('preview', SubmitType::class, [
                'label' => 'Preview Data',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('import', SubmitType::class, [
                'label' => 'Import Data',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $choiceFieldsWithPossibleSingleValues = ['tagId', 'objectType'];
            foreach ($choiceFieldsWithPossibleSingleValues as $field) {
                $choices = $form->get($field)->getConfig()->getOption('choices');
                if (count($choices) === 1) {
                    $form->get($field)->setData(array_key_first($choices));
                }
            }
        });
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
