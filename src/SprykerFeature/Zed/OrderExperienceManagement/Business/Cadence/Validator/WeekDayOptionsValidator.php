<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator;

use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class WeekDayOptionsValidator implements WeekDayOptionsValidatorInterface
{
    protected const string GLOSSARY_KEY_DAYS_REQUIRED = 'recurring_orders.cadence.validation.days_required';

    public function __construct(protected readonly OrderExperienceManagementConfig $orderExperienceManagementConfig)
    {
    }

    /**
     * @param array<string> $cadenceOptions
     */
    public function areWeekDaysValid(array $cadenceOptions): CadenceValidationResponseTransfer
    {
        $cadenceValidationResponseTransfer = (new CadenceValidationResponseTransfer())
            ->addRequiredCadenceField(SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_OPTIONS);

        if (!$this->areWeekDaysSupported($cadenceOptions)) {
            return $cadenceValidationResponseTransfer
                ->setIsSuccess(false)
                ->setMessage(static::GLOSSARY_KEY_DAYS_REQUIRED);
        }

        return $cadenceValidationResponseTransfer->setIsSuccess(true);
    }

    /**
     * @param array<string> $cadenceOptions
     */
    protected function areWeekDaysSupported(array $cadenceOptions): bool
    {
        if ($cadenceOptions === []) {
            return false;
        }

        $supportedWeekDays = $this->orderExperienceManagementConfig->getSupportedWeekDays();

        foreach ($cadenceOptions as $cadenceOption) {
            if (!is_numeric($cadenceOption)) {
                return false;
            }

            if (!in_array((int)$cadenceOption, $supportedWeekDays, true)) {
                return false;
            }
        }

        return true;
    }
}
