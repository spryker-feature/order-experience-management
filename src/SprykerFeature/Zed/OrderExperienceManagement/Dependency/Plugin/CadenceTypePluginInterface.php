<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use DateTimeImmutable;

/**
 * Defines a single recurring-order cadence type (e.g. weekly, monthly, every_n_weeks).
 */
interface CadenceTypePluginInterface
{
    /**
     * Specification:
     * - Returns the cadence_type value this plugin handles.
     * - Must match spy_recurring_schedule.cadence_type stored values.
     *
     * @api
     */
    public function getName(): string;

    /**
     * Specification:
     * - Computes the next trigger date from the given base date.
     * - Uses cadenceValue for interval-based types (e.g. every_n_weeks).
     * - Uses cadenceOptions for types parameterized by a set of values (e.g. by_days), the meaning of
     *   the entries is defined by the implementing cadence type.
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function getNextTriggerDate(
        DateTimeImmutable $currentTriggerDate,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable;

    /**
     * Specification:
     * - Returns whether this cadence type is parameterized by a numeric cadence value.
     * - A type that returns `false` stores no cadence value, consumers clear it when the type is selected.
     *
     * @api
     */
    public function isValueRequired(): bool;

    /**
     * Specification:
     * - Returns whether the given cadence value is acceptable for this cadence type.
     * - A type that is not parameterized by a value accepts any value, including `null`.
     *
     * @api
     */
    public function isValueValid(?int $cadenceValue): bool;

    /**
     * Specification:
     * - Returns whether this cadence type is parameterized by a set of cadence options.
     * - A type that returns `false` stores no cadence options, consumers clear them when the type is selected.
     *
     * @api
     */
    public function areOptionsRequired(): bool;

    /**
     * Specification:
     * - Returns whether the given cadence options are acceptable for this cadence type.
     * - A type that is not parameterized by options accepts any options, including an empty array.
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function areOptionsValid(array $cadenceOptions): bool;

    /**
     * Specification:
     * - Returns the translation key used in storefront dropdowns and email notifications.
     *
     * @api
     */
    public function getDisplayKey(): string;
}
