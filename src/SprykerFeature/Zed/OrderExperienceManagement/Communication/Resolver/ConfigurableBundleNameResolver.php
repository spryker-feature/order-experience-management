<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Resolver;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;

class ConfigurableBundleNameResolver
{
    public function __construct(
        protected readonly GlossaryFacadeInterface $glossaryFacade,
        protected readonly LocaleFacadeInterface $localeFacade,
    ) {
    }

    /**
     * @return array<string, string> Keys are configurable bundle name glossary keys, values are the translated names.
     */
    public function getTranslatedNamesByGlossaryKey(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $glossaryKeys = $this->extractGlossaryKeys($recurringScheduleTransfer);

        if ($glossaryKeys === []) {
            return [];
        }

        $translationTransfers = $this->glossaryFacade->getTranslationsByGlossaryKeysAndLocaleTransfers(
            $glossaryKeys,
            [$this->localeFacade->getCurrentLocale()],
        );

        return $this->indexTranslationValuesByGlossaryKey($translationTransfers);
    }

    /**
     * @return list<string>
     */
    protected function extractGlossaryKeys(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $glossaryKeys = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $glossaryKey = $recurringScheduleItemTransfer->getConfigurableBundleName();

            if ($glossaryKey === null) {
                continue;
            }

            $glossaryKeys[$glossaryKey] = true;
        }

        return array_keys($glossaryKeys);
    }

    /**
     * @param array<\Generated\Shared\Transfer\TranslationTransfer> $translationTransfers
     *
     * @return array<string, string>
     */
    protected function indexTranslationValuesByGlossaryKey(array $translationTransfers): array
    {
        $translatedNames = [];

        foreach ($translationTransfers as $translationTransfer) {
            $glossaryKey = $translationTransfer->getGlossaryKey()?->getKey();

            if ($glossaryKey === null || $translationTransfer->getValue() === null) {
                continue;
            }

            $translatedNames[$glossaryKey] = $translationTransfer->getValue();
        }

        return $translatedNames;
    }
}
