<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use DateTimeImmutable;
use Generated\Shared\Transfer\CadenceValidationResponseTransfer;

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
     * - Returns whether the given cadence parameters are acceptable for this cadence type.
     * - On failure the response carries the glossary key describing what is missing or invalid.
     * - The response also reports the cadence fields this type is parameterized by, as
     *   `OrderExperienceManagementConfig::CADENCE_FIELD_*` values, consumers clear every field absent
     *   from that list when the type is selected.
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(?int $cadenceValue, array $cadenceOptions): CadenceValidationResponseTransfer;

    /**
     * Specification:
     * - Returns the translation key used in storefront dropdowns and email notifications.
     *
     * @api
     */
    public function getDisplayKey(): string;
}
