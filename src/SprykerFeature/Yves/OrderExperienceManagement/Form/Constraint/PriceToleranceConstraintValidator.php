<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint;

use Spryker\DecimalObject\Decimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PriceToleranceConstraintValidator extends ConstraintValidator
{
    protected const string PARAMETER_DECIMALS = '{{ decimals }}';

    protected const string PARAMETER_STEP = '{{ step }}';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PriceToleranceConstraint) {
            throw new UnexpectedTypeException($constraint, PriceToleranceConstraint::class);
        }

        if (!is_numeric($value)) {
            return;
        }

        $priceTolerancePercent = Decimal::create((string)$value)->trim();

        if ($priceTolerancePercent->scale() > $constraint->scale) {
            $this->context->buildViolation($constraint->decimalsMessage)
                ->setParameter(static::PARAMETER_DECIMALS, (string)$constraint->scale)
                ->addViolation();

            return;
        }

        if (!$priceTolerancePercent->mod($constraint->step, $constraint->scale)->isZero()) {
            $this->context->buildViolation($constraint->stepMessage)
                ->setParameter(static::PARAMETER_STEP, $constraint->step)
                ->addViolation();
        }
    }
}
