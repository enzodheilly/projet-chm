<?php

namespace App\Form;

use App\Entity\Licence;
use App\Entity\Forfait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => true,
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro de licence',
                'required' => true,
            ])
            ->add('forfait', EntityType::class, [
                'class' => Forfait::class,
                'label' => 'Forfait choisi',
                'choice_label' => function (Forfait $forfait) {
                    return sprintf(
                        '%s - %.2f€ / an',
                        strtoupper($forfait->getNom()),
                        $forfait->getPrix()
                    );
                },
                'placeholder' => 'Sélectionnez un forfait',
                'required' => true,
            ])
            ->add('expiryDate', DateType::class, [
                'label' => 'Date d’expiration',
                'widget' => 'single_text',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Licence::class,
        ]);
    }
}
