<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date;

use DateTimeImmutable;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;

class FirstTriggerDateResolver implements FirstTriggerDateResolverInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    public function __construct(protected readonly CadenceResolverInterface $cadenceResolver)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(?string $startDate, string $cadenceType, ?int $cadenceValue): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today');
        $startDateTime = $this->parseStartDate($startDate) ?? $today;

        if ($startDateTime > $today) {
            return $startDateTime;
        }

        return $this->cadenceResolver->resolveNextTriggerDateFromBase($cadenceType, $cadenceValue, $startDateTime);
    }

    protected function parseStartDate(?string $startDate): ?DateTimeImmutable
    {
        if ($startDate === null || $startDate === '') {
            return null;
        }

        return DateTimeImmutable::createFromFormat('!' . static::DATE_FORMAT, $startDate) ?: null;
    }
}
