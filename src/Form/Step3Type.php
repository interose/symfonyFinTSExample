<?php

namespace App\Form;

use Fhp\Model\SEPAAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class Step3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('subaccounts', ChoiceType::class, [
            'label' => 'Please select',
            'choices' => $options['subaccounts'],
            'placeholder' => 'Please select',
            'choice_label' => fn (SEPAAccount $account) => 'IBAN: '.$account->getIban(),
            'choice_value' => fn (?SEPAAccount $account) => $account?->getIban(),
            'multiple' => false,
            'expanded' => false,
            'constraints' => [
                new NotBlank(),
            ],
            'help' => 'Iban and account number will be saved encrypted in the database.',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'attr' => [
                'novalidate' => true,
            ],
            'subaccounts' => [],
        ]);
    }
}
