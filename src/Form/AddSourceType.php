<?php

namespace App\Form;

use App\Entity\Source;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddSourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customName', TextType::class, [
                'label' => 'Nom du flux',
                'attr' => ['placeholder' => 'Entrez un nom pour votre flux'],
                'mapped' => false,
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL du flux RSS',
                'attr' => ['placeholder' => 'Entrez l\'URL du flux RSS'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Source::class,
        ]);
    }
}
