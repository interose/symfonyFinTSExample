<?php

namespace App\Form;

use Fhp\Model\TanMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class Step2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var TanMode[] $tanModeChoices */
        $tanModeChoices = $options['tanModeChoices'] ?? [];

        if (count($tanModeChoices) > 0) {
            $choicesAttr = [];
            $choices = ['Please select' => ''];

            foreach ($tanModeChoices as $tanModeChoice) {
                $choices[$tanModeChoice->getName()] = $tanModeChoice->getId();

                $choicesAttr[$tanModeChoice->getName()] = [
                    'data-needs-tan-medium' => $tanModeChoice->needsTanMedium() ? 'true' : 'false',
                    'data-is-decoupled' => $tanModeChoice->isDecoupled() ? 'true' : 'false',
                ];
            }

            $builder
                ->add('tanMechanism', ChoiceType::class, [
                    'label' => 'TAN Mode',
                    'choices' => $choices,
                    'choice_attr' => $choicesAttr,
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'help' => 'TAN mode will be saved encrypted in the database.',
                ])
                ->add('tanMediaName', ChoiceType::class, [
                    'label' => 'TAN Media Name',
                    'attr' => [
                        'disabled' => 'disabled',
                    ],
                    'choices' => [
                        'Please select' => '',
                    ],
                    'empty_data' => '',
                    'help' => 'TAN media will be saved encrypted in the database.',
                ]);

            // this is needed because the possible choices are added via javascript
            $builder->get('tanMediaName')->resetViewTransformers();
        } else {
            $builder
                ->add('tanMechanism', TextType::class, [
                    'label' => 'TAN Mode',
                    'attr' => [
                        'placeholder' => ' ',
                    ],
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'help' => 'TAN mode will be saved encrypted in the database.',
                ])
                ->add('tanMediaName', TextType::class, [
                    'label' => 'TAN Medium',
                    'attr' => [
                        'placeholder' => ' ',
                    ],
                    'help' => 'TAN media will be saved encrypted in the database.',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'attr' => [
                'novalidate' => true,
            ],
            'tanModeChoices' => [],
        ]);
    }
}
