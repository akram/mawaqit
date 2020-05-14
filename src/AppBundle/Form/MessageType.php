<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Entity\Message;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;


class MessageType extends AbstractType
{

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('title', null, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'message.form.title.placeholder',
                    'maxlength' => "40",
                    'class' => 'keyboardInput',
                ]
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 5000
                    ])
                ],
                'attr' => [
                    'class' => "tinymce keyboardInput",
                    'placeholder' => 'message.form.content.placeholder'
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'help' => $this->translator->trans('message.form.enabled.title'),
                ]
            ])
            ->add('mobile', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'help' => $this->translator->trans('message.form.mobile.title'),
                ]
            ])->add('desktop', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'help' => $this->translator->trans('message.form.desktop.title'),
                ]
            ])
            ->add('file', ImageType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'help' => $this->translator->trans('message.form.image.title'),
                ]
            ])
            ->add('video', UrlType::class, [
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=XXXXXXXXXXX',
                    'help' => $this->translator->trans('message.form.video.title'),
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'attr' => [
                    'class' => 'btn btn-primary',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'label_format' => 'message.form.%name%.label',
            'data_class' => Message::class,
            'required' => false,
        ));
    }

}
