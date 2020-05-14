<?php

namespace AppBundle\Validator\Constraints;

use IslamicNetwork\PrayerTimes\Method;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use AppBundle\Entity\Configuration;

class ConfigurationValidator extends ConstraintValidator
{

    /**
     * @param Configuration $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        // validate degrees
        if ($value->getSourceCalcul() === Configuration::SOURCE_API && $value->getPrayerMethod() === Method::METHOD_CUSTOM) {
            if (empty($value->getFajrDegree()) || empty($value->getIshaDegree())) {
                $this->context->buildViolation($constraint->m1)->addViolation();
            }
        }

        // validate dst dates
        if ($value->getDst() === 1 && ($value->getDstSummerDate() === null || $value->getDstWinterDate() === null)) {
            $this->context->buildViolation($constraint->m2)->addViolation();
        }

        // validate jumua
        if ($value->hasJumua() && empty($value->getJumuaTime())) {
            $this->context->buildViolation($constraint->m3)->addViolation();
        }

        // validate calendar
        foreach ($value->getDecodedCalendar() as $month) {
            foreach ($month as $prayers) {
                foreach ($prayers as $prayer) {
                    if (!empty($prayer) && !preg_match("/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $prayer)) {
                        $this->context->buildViolation($constraint->m4)->addViolation();
                        return;
                    }
                }
            }
        }
    }

}
