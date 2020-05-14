<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    /**
     * @var string
     */
    private $passwordPattern;

    public function __construct($passwordPattern)
    {
        $this->passwordPattern = $passwordPattern;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove("username");

        $builder
            ->add(
                'tou',
                CheckboxType::class,
                [
                    'required' => true,
                ]
            )
            ->add(
                'recaptcha',
                EWZRecaptchaType::class,
                [
                    'label' => false
                ]
            )
            ->add(
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
            )
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    public function onSubmit(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getData();
        $user->setUsername($user->getEmail());
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'label_format' => 'registration.form.%name%.label',
            )
        );
    }

}
