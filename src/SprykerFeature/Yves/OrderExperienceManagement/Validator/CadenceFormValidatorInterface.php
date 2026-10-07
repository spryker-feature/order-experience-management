<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Validator;

use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface CadenceFormValidatorInterface
{
    public function validateCheckoutCadence(
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
        ExecutionContextInterface $context
    ): void;

    /**
     * @param array<string, mixed>|null $formData
     */
    public function validateScheduleEditCadence(?array $formData, ExecutionContextInterface $context): void;
}
