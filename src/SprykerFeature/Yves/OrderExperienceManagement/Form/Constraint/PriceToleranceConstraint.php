<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint;

use Symfony\Component\Validator\Constraint;

class PriceToleranceConstraint extends Constraint
{
    /**
     * @param array<string>|null $groups
     */
    public function __construct(
        public readonly int $scale,
        public readonly string $step,
        public readonly string $decimalsMessage,
        public readonly string $stepMessage,
        ?array $groups = null,
    ) {
        parent::__construct(groups: $groups);
    }

    public function validatedBy(): string
    {
        return PriceToleranceConstraintValidator::class;
    }
}
