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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 */
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
     * @param array<string> $cadenceOptions
     *
     * @throws \SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException
     */
    public function getNextTriggerDate(
        DateTimeImmutable $currentTriggerDate,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): DateTimeImmutable {
        if (!$this->isCadenceValid($cadenceValue, $cadenceOptions)->getIsSuccess()) {
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
     *
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(?int $cadenceValue, array $cadenceOptions): CadenceValidationResponseTransfer
    {
        return $this->getBusinessFactory()
            ->createWeekIntervalValidator()
            ->isIntervalValid($cadenceValue);
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
