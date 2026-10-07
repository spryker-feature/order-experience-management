<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement;

use DateTimeImmutable;
use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator\WeekDayOptionsValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayOptionsReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayTriggerDateCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class ByDaysCadenceTypePluginStub implements CadenceTypePluginInterface
{
    public function getName(): string
    {
        return SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS;
    }

    /**
     * @param array<string> $cadenceOptions
     */
    public function getNextTriggerDate(
        DateTimeImmutable $currentTriggerDate,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable {
        return (new WeekDayTriggerDateCalculator(new WeekDayOptionsReader()))
            ->calculateNextTriggerDate($currentTriggerDate, $cadenceOptions);
    }

    /**
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(?int $cadenceValue, array $cadenceOptions): CadenceValidationResponseTransfer
    {
        return (new WeekDayOptionsValidator(new OrderExperienceManagementConfig()))
            ->areWeekDaysValid($cadenceOptions);
    }

    public function getDisplayKey(): string
    {
        return 'recurring_orders.cadence.by_days';
    }
}
