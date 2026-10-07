<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence;

use DateTimeImmutable;
use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 */
class ByDaysCadenceTypePlugin extends AbstractPlugin implements CadenceTypePluginInterface
{
    protected const string DISPLAY_KEY = 'recurring_orders.cadence.by_days';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return OrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function getNextTriggerDate(
        DateTimeImmutable $currentTriggerDate,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable {
        return $this->getBusinessFactory()
            ->createWeekDayTriggerDateCalculator()
            ->calculateNextTriggerDate($currentTriggerDate, $cadenceOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(?int $cadenceValue, array $cadenceOptions): CadenceValidationResponseTransfer
    {
        return $this->getBusinessFactory()
            ->createWeekDayOptionsValidator()
            ->areWeekDaysValid($cadenceOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDisplayKey(): string
    {
        return static::DISPLAY_KEY;
    }
}
