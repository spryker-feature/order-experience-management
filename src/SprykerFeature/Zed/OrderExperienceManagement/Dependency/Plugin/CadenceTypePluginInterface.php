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
     *
     * @api
     */
    public function getNextTriggerDate(DateTimeImmutable $currentTriggerDate, ?int $cadenceValue): DateTimeImmutable;

    /**
     * Specification:
     * - Returns the translation key used in storefront dropdowns and email notifications.
     *
     * @api
     */
    public function getDisplayKey(): string;
}
