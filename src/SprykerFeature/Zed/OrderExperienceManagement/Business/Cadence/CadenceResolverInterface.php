<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use DateTimeImmutable;
use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface CadenceResolverInterface
{
    public function resolveNextTriggerDate(RecurringScheduleTransfer $recurringScheduleTransfer): DateTimeImmutable;

    /**
     * @param array<string> $cadenceOptions
     */
    public function resolveNextTriggerDateFromBase(
        string $cadenceType,
        ?int $cadenceValue,
        DateTimeImmutable $baseDate,
        array $cadenceOptions = [],
    ): DateTimeImmutable;

    public function isSupported(string $cadenceType): bool;

    /**
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(
        string $cadenceType,
        ?int $cadenceValue,
        array $cadenceOptions
    ): CadenceValidationResponseTransfer;
}
