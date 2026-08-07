<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast;

use DateTimeImmutable;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;

class MonthlyOccurrenceCounter implements MonthlyOccurrenceCounterInterface
{
    protected const int MAX_ITERATIONS = 31;

    public function __construct(protected readonly CadenceResolverInterface $cadenceResolver)
    {
    }

    public function count(
        ?string $cadenceType,
        ?int $cadenceValue,
        ?string $anchorDate,
        DateTimeImmutable $referenceDate
    ): int {
        if ($cadenceType === null || $anchorDate === null) {
            return 0;
        }

        if (!$this->cadenceResolver->isSupported($cadenceType)) {
            return 0;
        }

        if ($this->cadenceResolver->isValueRequired($cadenceType) && ($cadenceValue === null || $cadenceValue < 1)) {
            return 0;
        }

        $nextMonthStart = $referenceDate->modify('first day of next month')->setTime(0, 0, 0);

        $occurrenceDate = (new DateTimeImmutable($anchorDate))->setTime(0, 0, 0);
        $occurrenceCount = 0;
        $iterations = 0;

        while ($occurrenceDate < $nextMonthStart && $iterations < static::MAX_ITERATIONS) {
            $occurrenceCount++;
            $occurrenceDate = $this->cadenceResolver->resolveNextTriggerDateFromBase($cadenceType, $cadenceValue, $occurrenceDate);
            $iterations++;
        }

        return $occurrenceCount;
    }
}
