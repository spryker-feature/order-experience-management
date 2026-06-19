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
     * @throws \InvalidArgumentException
     */
    public function getNextTriggerDate(DateTimeImmutable $currentTriggerDate, ?int $cadenceValue): DateTimeImmutable
    {
        if ($cadenceValue === null || $cadenceValue < 1) {
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
    public function getDisplayKey(): string
    {
        return static::DISPLAY_KEY;
    }
}
