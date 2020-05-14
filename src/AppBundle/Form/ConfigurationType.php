<?php

namespace AppBundle\Form;

use AppBundle\Entity\Configuration;
use AppBundle\Entity\Mosque;
use AppBundle\Form\DataTransformer\PrayerTransformer;
use AppBundle\Service\PrayerTime;
use AppBundle\Service\ToolsService;
use IslamicNetwork\PrayerTimes\PrayerTimes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class ConfigurationType extends AbstractType
{

    /**
     * @var array
     */
    private static $DST = [
        "dst-auto" => 2,
        "dst-disabled" => 0,
        "dst-enabled" => 1
    ];
    /**
     * @var array
     */
    private static $THEMES = [
        "mawaqit",
        "spring",
        "summer",
        "autumn",
        "winter"
    ];
    /**
     * @var array
     */
    private static $RANDOM_HADITH_INTERVAL_DISABLING = [
        "" => "",
        "fajr-zuhr" => "0-1",
        "zuhr-asr" => "1-2",
        "asr-maghrib" => "2-3",
        "maghrib-isha" => "3-4"
    ];
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationChecker
     */
    private $securityChecker;

    public function __construct(TranslatorInterface $translator, AuthorizationChecker $securityChecker)
    {
        $this->translator = $translator;
        $this->securityChecker = $securityChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isAdmin = $this->securityChecker->isGranted('ROLE_ADMIN');
        $adjustedTimesValues = range(-30, 30);
        $timePattern = '/^\d{2}:\d{2}$/';

        $builder
            ->add(
                'jumuaTime',
                null,
                [
                    'constraints' => new Regex(['pattern' => $timePattern]),
                    'attr' => [
                        'placeholder' => 'hh:mm'
                    ]
                ]
            )
            ->add(
                'jumuaTime2',
                null,
                [
                    'constraints' => new Regex(['pattern' => $timePattern]),
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.jumuaTime2.title'),
                        'placeholder' => 'hh:mm'
                    ]
                ]
            )
            ->add(
                'jumuaAsDuhr',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'jumua',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'jumuaDhikrReminderEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => 'configuration.form.jumuaDhikrReminderEnabled.title',
                    ]
                ]
            )
            ->add(
                'jumuaBlackScreenEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'jumuaTimeout',
                IntegerType::class,
                [
                    'constraints' => new GreaterThanOrEqual(['value' => 20]),
                    'attr' => [
                        'min' => 20
                    ]
                ]
            )
            ->add(
                'aidTime',
                null,
                [
                    'constraints' => new Regex(['pattern' => $timePattern]),
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.aidTime.title'),
                        'placeholder' => 'hh:mm'
                    ]
                ]
            )
            ->add(
                'imsakNbMinBeforeFajr',
                IntegerType::class,
                [
                    'constraints' => new GreaterThanOrEqual(['value' => 0]),
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.imsakNbMinBeforeFajr.title'),
                        'min' => 0
                    ]
                ]
            )
            ->add(
                'maximumIshaTimeForNoWaiting',
                null,
                [
                    'constraints' => new Regex(['pattern' => $timePattern]),
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.maximumIshaTimeForNoWaiting.title'),
                        'placeholder' => 'hh:mm',
                    ]
                ]
            )
            ->add(
                'waitingTimes',
                PrayerType::class,
                [
                    'sub_options' => [
                        'type' => IntegerType::class,
                        'constraints' => [new GreaterThanOrEqual(['value' => 0]), new NotBlank()],
                        'attr' => [
                            'min' => 0
                        ]
                    ]
                ]
            )
            ->add(
                'adjustedTimes',
                PrayerType::class,
                [
                    'constraints' => new NotBlank(['message' => "form.configuration.mandatory"]),
                    'sub_options' => [
                        'type' => ChoiceType::class,
                        'choices' => array_combine($adjustedTimesValues, $adjustedTimesValues)

                    ]
                ]
            )
            ->add(
                'fixedTimes',
                PrayerType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.fixedTimes.title')
                    ],
                    'sub_options' => [
                        'type' => TextType::class,
                        'constraints' => new Regex(['pattern' => $timePattern]),
                        'attr' => [
                            'placeholder' => "hh:mm"
                        ]
                    ]
                ]
            )
            ->add(
                'fixedIqama',
                PrayerType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.fixedIqama.title')
                    ],
                    'sub_options' => [
                        'type' => TextType::class,
                        'constraints' => new Regex(['pattern' => $timePattern]),
                        'attr' => [
                            'placeholder' => "hh:mm"
                        ]
                    ]
                ]
            )
            ->add(
                'duaAfterPrayerShowTimes',
                PrayerType::class,
                [
                    'sub_options' => [
                        'type' => IntegerType::class,
                        'constraints' => [new GreaterThanOrEqual(['value' => 5]), new NotBlank()],
                        'attr' => [
                            'min' => 5
                        ]
                    ],
                    'attr' => [
                        'help' => $this->translator->trans('configuration.form.duaAfterPrayerShowTimes.title'),
                    ],
                ]
            )
            ->add(
                'hijriAdjustment',
                ChoiceType::class,
                [
                    'choices' => [-2 => -2, -1 => -1, 0 => 0, 1 => 1, 2 => 2],
                ]
            )
            ->add(
                'timezoneName',
                TimezoneType::class,
                [
                    'disabled' => !$isAdmin,
                ]
            )
            ->add(
                'dst',
                ChoiceType::class,
                [
                    'choices' => self::$DST,
                ]
            )
            ->add(
                'dstSummerDate',
                DateType::class,
                [
                    'required' => false,
                    'widget' => 'choice',
                    'attr' => [
                        'help' => 'configuration.form.dstSummerDate.title',
                    ],
                ]
            )
            ->add(
                'dstWinterDate',
                DateType::class,
                [
                    'required' => false,
                    'widget' => 'choice',
                    'attr' => [
                        'help' => 'configuration.form.dstWinterDate.title',
                    ],
                ]
            )
            ->add(
                'hadithLang',
                ChoiceType::class,
                [
                    'choices' => array_combine(Configuration::HADITH_LANG, Configuration::HADITH_LANG),
                ]
            )
            ->add(
                'asrMethod',
                ChoiceType::class,
                [
                    'choices' => [
                        "configuration.form.asrMethod.Standard" => PrayerTimes::MIDNIGHT_MODE_STANDARD,
                        "configuration.form.asrMethod.Hanafi" => PrayerTimes::SCHOOL_HANAFI,
                    ]
                ]
            )
            ->add(
                'highLatsMethod',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => [
                        "configuration.form.highLatsMethod.AngleBased" => PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE,
                        "configuration.form.highLatsMethod.NightMiddle" => PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_MOTN,
                        "configuration.form.highLatsMethod.OneSeventh" => PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ONESEVENTH
                    ]
                ]
            )
            ->add(
                'hijriDateEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'duaAfterAzanEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'duaAfterPrayerEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'azanBip',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'configuration.form.azanBip.label',
                ]
            )
            ->add(
                'azanVoiceEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => 'configuration.form.azanVoiceEnabled.title',
                    ],
                ]
            )
            ->add(
                'wakeAzanVoice',
                ChoiceType::class,
                [
                    'choices' => [
                        "configuration.form.wakeAzanVoice.haram" => "adhan-maquah",
                        "configuration.form.wakeAzanVoice.algeria" => "adhan-algeria",
                        "configuration.form.wakeAzanVoice.quds" => "adhan-quds",
                        "configuration.form.wakeAzanVoice.egypt" => "adhan-egypt",
                    ]
                ]
            )
            ->add(
                'iqamaBip',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'iqamaFullScreenCountdown',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'blackScreenWhenPraying',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => 'configuration.form.blackScreenWhenPraying.title',
                    ],
                ]
            )
            ->add(
                'sourceCalcul',
                ChoiceType::class,
                [
                    'choices' => array_combine(Configuration::SOURCE_CHOICES, Configuration::SOURCE_CHOICES)
                ]
            )
            ->add(
                'prayerMethod',
                ChoiceType::class,
                [
                    'choices' => array_combine(PrayerTime::METHOD_CHOICES, PrayerTime::METHOD_CHOICES)
                ]
            )
            ->add(
                'fajrDegree',
                NumberType::class,
                [
                    'scale' => 2,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'configuration.form.fajrDegree.placeholder',
                    ]
                ]
            )
            ->add(
                'ishaDegree',
                NumberType::class,
                [
                    'scale' => 2,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'configuration.form.ishaDegree.placeholder',
                    ]
                ]
            )
            ->add(
                'iqamaDisplayTime',
                IntegerType::class,
                [
                    'constraints' => new GreaterThanOrEqual(['value' => 5]),
                    'label' => 'configuration.form.iqamaDisplayTime.label',
                    'attr' => [
                        'min' => 5
                    ]
                ]
            )
            ->add(
                'wakeForFajrTime',
                IntegerType::class,
                [
                    'required' => false,
                    'constraints' => new GreaterThanOrEqual(['value' => 0]),
                    'attr' => [
                        'min' => 0,
                        'help' => 'configuration.form.wakeForFajrTime.title',
                    ]
                ]
            )
            ->add(
                'ishaFixation',
                ChoiceType::class,
                [
                    'required' => false,
                    'placeholder' => 'select_a_value',
                    'choices' => [
                        '1h05' => 65,
                        '1h10' => 70,
                        '1h15' => 75,
                        '1h20' => 80,
                        '1h30' => 90,
                        '1h45' => 105,
                        '2h00' => 120,
                    ],
                    'attr' => [
                        'help' => 'configuration.form.ishaFixation.title',
                    ]
                ]
            )
            ->add(
                'randomHadithEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'configuration.form.randomHadithEnabled.label',
                    'attr' => [
                        'help' => 'configuration.form.randomHadithEnabled.title',
                    ]
                ]
            )
            ->add(
                'randomHadithIntervalDisabling',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => self::$RANDOM_HADITH_INTERVAL_DISABLING,
                    'attr' => [
                        'help' => 'configuration.form.randomHadithIntervalDisabling.title',
                    ]
                ]
            )
            ->add(
                'iqamaEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'configuration.form.iqamaEnabled.label',
                    'attr' => [
                        'help' => 'configuration.form.iqamaEnabled.title',
                    ]
                ]
            )
            ->add(
                'temperatureEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => [
                        'help' => 'configuration.form.temperatureEnabled.title',
                    ]
                ]
            )
            ->add(
                'temperatureUnit',
                ChoiceType::class,
                [
                    'choices' => ["°C" => "C", "°F" => "F"],
                    'constraints' => [
                        new Choice(['choices' => ["C", "F"]]),
                        new NotBlank(),
                    ],
                    'expanded' => true,
                    'label_attr' => array(
                        'class' => 'radio-inline'
                    )
                ]
            )
            ->add(
                'footer',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'configuration.form.footer.label'
                ]
            )
            ->add(
                'timeDisplayFormat',
                ChoiceType::class,
                [
                    'choices' => ["24h" => "24", "12h" => "12"],
                    'constraints' => [
                        new Choice(['choices' => ["24", "12"]]),
                        new NotBlank(),
                    ],
                    'expanded' => true,
                    'label_attr' => array(
                        'class' => 'radio-inline'
                    )
                ]
            )
            ->add(
                'theme',
                ChoiceType::class,
                [
                    'choices' => array_combine(self::$THEMES, self::$THEMES),
                    'constraints' => [
                        new Choice(['choices' => self::$THEMES]),
                        new NotBlank(),
                    ]
                ]
            )
            ->add(
                'backgroundType',
                ChoiceType::class,
                [
                    'choices' => ["color" => "color", "motif" => "motif"],
                    'constraints' => [
                        new Choice(['choices' => ["color", "motif"]]),
                        new NotBlank(),
                    ]
                ]
            )
            ->add(
                'backgroundMotif',
                ChoiceType::class,
                [
                    'choices' => range(-1, 20),
                    'constraints' => [
                        new NotBlank(),
                    ]
                ]
            )
            ->add('backgroundColor')
            ->add('calendar')
            ->add('iqamaCalendar')
            ->add(
                'timeToDisplayMessage',
                IntegerType::class,
                [
                    'required' => false,
                    'constraints' => new Range(['min' => 5, 'max' => 60]),
                    'attr' => [
                        'min' => 5,
                        'max' => 60
                    ]
                ]
            )
            ->add(
                'showNextAdhanCountdown',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'save',
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ]
                ]
            );

        $builder->get('waitingTimes')->addModelTransformer(new PrayerTransformer());
        $builder->get('adjustedTimes')->addModelTransformer(new PrayerTransformer());
        $builder->get('fixedTimes')->addModelTransformer(new PrayerTransformer());
        $builder->get('fixedIqama')->addModelTransformer(new PrayerTransformer());
        $builder->get('duaAfterPrayerShowTimes')->addModelTransformer(new PrayerTransformer());

        $builder->get('calendar')
            ->addViewTransformer(
                new CallbackTransformer(
                    function ($value) {
                        return json_decode($value, true);
                    },
                    function ($value) {
                        return $value;
                    }
                )
            );

        $builder->get('iqamaCalendar')
            ->addViewTransformer(
                new CallbackTransformer(
                    function ($value) {
                        return json_decode($value, true);
                    },
                    function ($value) {
                        return $value;
                    }
                )
            );
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Configuration::class,
                'choice_translation_domain' => true,
                'label_format' => 'configuration.form.%name%.label'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'configuration';
    }

}
