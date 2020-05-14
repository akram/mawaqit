<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MessageValidator extends ConstraintValidator
{
    public const MAX_ENABLED_MESSAGE = 8;
    public const MAX_MESSAGE = 20;

    public function validate($message, Constraint $constraint)
    {
        /**
         * @var $message \AppBundle\Entity\Message
         */

        if (empty($message->getContent()) && empty($message->getFile()) && empty($message->getImage()) && empty($message->getVideo())) {
            $this->context->buildViolation($constraint->minmumContentRequired)->addViolation();
            return;
        }

        if ($message->getMosque()->getMessages()->count() > self::MAX_MESSAGE) {
            $this->context->buildViolation($constraint->messageMaxReached)->addViolation();
            return;
        }

        if ($message->isEnabled() && $message->getMosque()->getNbOfEnabledMessages() > self::MAX_ENABLED_MESSAGE) {
            $this->context->buildViolation($constraint->messageMaxEnabledReached)->addViolation();
        }
    }
}

