<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use DateTimeImmutable;
use Generated\Shared\Transfer\CadenceValidationResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\UnsupportedCadenceTypeException;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;

class CadenceResolver implements CadenceResolverInterface
{
    protected const string GLOSSARY_KEY_CADENCE_TYPE_UNSUPPORTED = 'recurring_orders.checkout.validation.cadence_required';

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface> $cadenceTypePlugins
     */
    public function __construct(protected readonly array $cadenceTypePlugins)
    {
    }

    public function resolveNextTriggerDate(RecurringScheduleTransfer $recurringScheduleTransfer): DateTimeImmutable
    {
        return $this->resolveNextTriggerDateFromBase(
            $recurringScheduleTransfer->getCadenceTypeOrFail(),
            $recurringScheduleTransfer->getCadenceValue(),
            new DateTimeImmutable($recurringScheduleTransfer->getNextTriggerDateOrFail()),
            $this->getScheduleCadenceOptions($recurringScheduleTransfer),
        );
    }

    /**
     * @param array<string> $cadenceOptions
     *
     * @throws \SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\UnsupportedCadenceTypeException
     */
    public function resolveNextTriggerDateFromBase(
        string $cadenceType,
        ?int $cadenceValue,
        DateTimeImmutable $baseDate,
        array $cadenceOptions = [],
    ): DateTimeImmutable {
        $cadenceTypePlugin = $this->findCadenceTypePlugin($cadenceType);

        if ($cadenceTypePlugin === null) {
            throw new UnsupportedCadenceTypeException(
                sprintf('No cadence type plugin registered for type "%s".', $cadenceType),
            );
        }

        return $cadenceTypePlugin->getNextTriggerDate($baseDate, $cadenceValue, $cadenceOptions);
    }

    public function isSupported(string $cadenceType): bool
    {
        return $this->findCadenceTypePlugin($cadenceType) !== null;
    }

    /**
     * @param array<string> $cadenceOptions
     */
    public function isCadenceValid(
        string $cadenceType,
        ?int $cadenceValue,
        array $cadenceOptions,
    ): CadenceValidationResponseTransfer {
        $cadenceTypePlugin = $this->findCadenceTypePlugin($cadenceType);

        if ($cadenceTypePlugin === null) {
            return (new CadenceValidationResponseTransfer())
                ->setIsSuccess(false)
                ->setMessage(static::GLOSSARY_KEY_CADENCE_TYPE_UNSUPPORTED);
        }

        return $cadenceTypePlugin->isCadenceValid($cadenceValue, $cadenceOptions);
    }

    /**
     * @return array<string>
     */
    protected function getScheduleCadenceOptions(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return array_map('strval', $recurringScheduleTransfer->getCadenceOptions());
    }

    protected function findCadenceTypePlugin(string $cadenceType): ?CadenceTypePluginInterface
    {
        foreach ($this->cadenceTypePlugins as $cadenceTypePlugin) {
            if ($cadenceTypePlugin->getName() === $cadenceType) {
                return $cadenceTypePlugin;
            }
        }

        return null;
    }
}
