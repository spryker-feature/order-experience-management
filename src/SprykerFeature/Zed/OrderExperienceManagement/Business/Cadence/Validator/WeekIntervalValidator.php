<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator;

use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class WeekIntervalValidator implements WeekIntervalValidatorInterface
{
    protected const int MINIMUM_INTERVAL_IN_WEEKS = 1;

    protected const string GLOSSARY_KEY_VALUE_REQUIRED = 'recurring_orders.cadence.validation.value_required';

    public function isIntervalValid(?int $cadenceValue): CadenceValidationResponseTransfer
    {
        $cadenceValidationResponseTransfer = (new CadenceValidationResponseTransfer())
            ->addRequiredCadenceField(SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_VALUE);

        if ($cadenceValue === null || $cadenceValue < static::MINIMUM_INTERVAL_IN_WEEKS) {
            return $cadenceValidationResponseTransfer
                ->setIsSuccess(false)
                ->setMessage(static::GLOSSARY_KEY_VALUE_REQUIRED);
        }

        return $cadenceValidationResponseTransfer->setIsSuccess(true);
    }
}
