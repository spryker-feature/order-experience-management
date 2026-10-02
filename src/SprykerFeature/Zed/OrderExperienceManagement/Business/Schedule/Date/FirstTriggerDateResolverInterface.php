<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date;

use DateTimeImmutable;

interface FirstTriggerDateResolverInterface
{
    /**
     * @param array<string> $cadenceOptions
     */
    public function resolve(
        ?string $startDate,
        string $cadenceType,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable;
}
