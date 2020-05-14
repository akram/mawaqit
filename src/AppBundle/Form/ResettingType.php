<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class ResettingType extends AbstractType
{
    /**
     * @var string
     */
    private $passwordPattern;

    public function __construct($passwordPattern)
    {
        $this->passwordPattern = $passwordPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'plainPassword',
            PasswordType::class,
            [
                'translation_domain' => 'FOSUserBundle',
                'label' => 'form.password',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'pattern' => $this->passwordPattern,
                ],
            ]
        );
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ResettingFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_resseting';
    }
}
