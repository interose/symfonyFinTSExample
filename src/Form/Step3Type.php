<?php

namespace App\Form;

use App\Lib\FinTs\Subaccount;
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
            'choice_label' => static function (Subaccount $subaccount): string {
                $type = $subaccount->capabilities->getAccountTypeLabel();

                return 'IBAN: '.$subaccount->account->getIban().(null !== $type ? ' ('.$type.')' : '');
            },
            'choice_value' => fn (?Subaccount $subaccount) => $subaccount?->account->getIban(),
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
