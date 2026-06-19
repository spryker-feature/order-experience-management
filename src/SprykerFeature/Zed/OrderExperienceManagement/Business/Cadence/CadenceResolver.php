<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\UnsupportedCadenceTypeException;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;

class CadenceResolver implements CadenceResolverInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface> $cadenceTypePlugins
     */
    public function __construct(protected readonly array $cadenceTypePlugins)
    {
    }

    public function resolveNextTriggerDate(RecurringScheduleTransfer $recurringScheduleTransfer): DateTimeImmutable
    {
        $cadenceType = $recurringScheduleTransfer->getCadenceTypeOrFail();
        $cadenceTypePlugin = $this->findCadenceTypePlugin($cadenceType);

        if ($cadenceTypePlugin === null) {
            throw new UnsupportedCadenceTypeException(
                sprintf('No cadence type plugin registered for type "%s".', $cadenceType),
            );
        }

        $baseDate = new DateTimeImmutable($recurringScheduleTransfer->getNextTriggerDateOrFail());

        return $cadenceTypePlugin->getNextTriggerDate($baseDate, $recurringScheduleTransfer->getCadenceValue());
    }

    public function resolveNextTriggerDateFromBase(string $cadenceType, ?int $cadenceValue, DateTimeImmutable $baseDate): DateTimeImmutable
    {
        $cadenceTypePlugin = $this->findCadenceTypePlugin($cadenceType);

        if ($cadenceTypePlugin === null) {
            throw new UnsupportedCadenceTypeException(
                sprintf('No cadence type plugin registered for type "%s".', $cadenceType),
            );
        }

        return $cadenceTypePlugin->getNextTriggerDate($baseDate, $cadenceValue);
    }

    public function isSupported(string $cadenceType): bool
    {
        return $this->findCadenceTypePlugin($cadenceType) !== null;
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
