<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

interface WeekDayOptionsReaderInterface
{
    /**
     * @param array<string> $cadenceOptions
     *
     * @return array<int>
     */
    public function extractWeekDays(array $cadenceOptions): array;
}
