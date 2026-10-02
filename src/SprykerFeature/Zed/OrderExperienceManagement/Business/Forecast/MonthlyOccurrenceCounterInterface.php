<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast;

use DateTimeImmutable;

interface MonthlyOccurrenceCounterInterface
{
    /**
     * @param array<string> $cadenceOptions
     */
    public function count(
        ?string $cadenceType,
        ?int $cadenceValue,
        ?string $anchorDate,
        DateTimeImmutable $referenceDate,
        array $cadenceOptions = []
    ): int;
}
