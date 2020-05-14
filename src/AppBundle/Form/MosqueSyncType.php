<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MosqueSyncType extends AbstractType
{
    private $request;
    private $languages;

    /**
     * MosqueSyncType constructor.
     *
     * @param RequestStack $requestStack
     * @param array        $languages
     */
    public function __construct(RequestStack $requestStack, array $languages)
    {
        $this->request = $requestStack->getMasterRequest();
        $this->languages = $languages;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $languages = array_map(
            function ($lang) {
                return ucfirst(Intl::getLanguageBundle()->getLanguageName($lang, null, $this->request->getLocale()));
            },
            $this->languages
        );

        $builder
            ->add(
                'id',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => "mosqueScreen.fillId",
                    'attr' => [
                        'help' => "mosqueScreen.fillIdHelp",
                        'class' => 'keyboardInput'
                    ]
                ]
            )->add(
                'login',
                EmailType::class,
                [
                    'required' => true,
                    'label' => "mosqueScreen.login",
                    'attr' => [
                        'help' => "mosqueScreen.loginHelp",
                        'class' => 'keyboardInput'
                    ]
                ]
            )->add(
                'password',
                PasswordType::class,
                [
                    'required' => true,
                    'label' => "mosqueScreen.password",
                    'attr' => [
                        'help' => "mosqueScreen.passwordHelp",
                        'class' => 'keyboardInput'
                    ]
                ]
            )->add(
                'language',
                ChoiceType::class,
                [
                    'choices' => array_combine($languages, $this->languages),
                    'required' => true,
                    'label' => "mosqueScreen.language.label",
                    'placeholder' => "mosqueScreen.language.placeholder",
                ]
            )->add(
                'screen',
                ChoiceType::class,
                [
                    'choices' => [
                        'mosqueScreen.screen.choices.1' => 'prayer-times',
                        'mosqueScreen.screen.choices.2' => 'messages',
                    ],
                    'required' => true,
                    'label' => "mosqueScreen.screen.label",
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

}
