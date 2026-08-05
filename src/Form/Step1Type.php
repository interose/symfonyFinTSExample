<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class Step1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ING-DiBa AG ',
                ],
                'label' => 'Bankname',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('bic', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. INGDDEFFXXX',
                ],
                'label' => 'BIC (SWIFT-Code)',
                'constraints' => [
                    new NotBlank(),
                    new Bic(),
                ],
            ])
            ->add('bankCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 50010517',
                ],
                'label' => 'Bank code',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('url', UrlType::class, [
                'attr' => [
                    'placeholder' => 'https://fints.some-bank.com/fints',
                ],
                'label' => 'HBCI / FinTS Interface-URL',
                'constraints' => [
                    new NotBlank(),
                    new Url(),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'User',
                'attr' => [
                    'placeholder' => ' ',
                    'autocomplete' => 'new-password',
                ],
                'help' => 'Username will be stored encrypted in the database.',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'placeholder' => ' ',
                    'autocomplete' => 'new-password',
                ],
                'help' => 'Password will be stored encrypted in the database.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'attr' => [
                'novalidate' => true,
            ],
        ]);
    }
}
