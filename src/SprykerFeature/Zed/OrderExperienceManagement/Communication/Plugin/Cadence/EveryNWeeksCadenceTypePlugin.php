<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence;

use DateTimeImmutable;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;

class EveryNWeeksCadenceTypePlugin extends AbstractPlugin implements CadenceTypePluginInterface
{
    protected const string DISPLAY_KEY = 'recurring_orders.cadence.every_n_weeks';

    protected const int MINIMUM_INTERVAL_IN_WEEKS = 1;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return OrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     *
     * @throws \SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException
     */
    public function getNextTriggerDate(
        DateTimeImmutable $currentTriggerDate,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable {
        if (!$this->isValueValid($cadenceValue)) {
            throw new InvalidCadenceValueException(
                sprintf('Cadence type "%s" requires a positive integer cadence value.', $this->getName()),
            );
        }

        return $currentTriggerDate->modify(sprintf('+%d weeks', $cadenceValue));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isValueRequired(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isValueValid(?int $cadenceValue): bool
    {
        return $cadenceValue !== null && $cadenceValue >= static::MINIMUM_INTERVAL_IN_WEEKS;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function areOptionsRequired(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $cadenceOptions
     */
    public function areOptionsValid(array $cadenceOptions): bool
    {
        return true;
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
