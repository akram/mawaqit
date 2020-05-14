<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
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
        $constraintsOptions = array(
            'message' => 'fos_user.current_password.invalid',
        );

        if (!empty($options['validation_groups'])) {
            $constraintsOptions['groups'] = array(reset($options['validation_groups']));
        }

        $builder->add(
            'current_password',
            PasswordType::class,
            array(
                'label' => 'form.current_password',
                'translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                'constraints' => array(
                    new NotBlank(),
                    new UserPassword($constraintsOptions),
                ),
                'attr' => array(
                    'autocomplete' => 'current-password',
                ),
            )
        );

        $builder->add(
            'plainPassword',
            PasswordType::class,
            [
                'translation_domain' => 'FOSUserBundle',
                'label' => 'form.new_password',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'pattern' => $this->passwordPattern,
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ChangePasswordFormType';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'app_user_change_password';
    }
}
