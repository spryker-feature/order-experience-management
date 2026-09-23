<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator;

use Generated\Shared\Transfer\LocaleTransfer;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * `CheckoutErrorTransfer::message` is a glossary key by Spryker convention
 */
class OrderIntakeMessageTranslator implements OrderIntakeMessageTranslatorInterface
{
    public function __construct(
        protected readonly GlossaryFacadeInterface $glossaryFacade,
        protected readonly TranslatorFacadeInterface $translatorFacade,
        protected readonly LocaleFacadeInterface $localeFacade,
        protected readonly OrderExperienceManagementConfig $config,
    ) {
    }

    public function resolveLocale(?string $localeName): LocaleTransfer
    {
        if ($localeName !== null && $this->localeFacade->hasLocale($localeName)) {
            return $this->localeFacade->getLocale($localeName);
        }

        $fallbackLocaleName = $this->config->getFallbackLocaleName();

        if ($this->localeFacade->hasLocale($fallbackLocaleName)) {
            return $this->localeFacade->getLocale($fallbackLocaleName);
        }

        return (new LocaleTransfer())->setLocaleName($fallbackLocaleName);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters
     */
    public function translateCheckoutError(string $glossaryKey, array $parameters, LocaleTransfer $localeTransfer): string
    {
        if (!$this->glossaryFacade->hasTranslation($glossaryKey, $localeTransfer)) {
            return $glossaryKey;
        }

        return $this->glossaryFacade->translate($glossaryKey, $parameters, $localeTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters
     */
    public function translateValidationIssue(string $translationKey, array $parameters, LocaleTransfer $localeTransfer): string
    {
        $localeName = $localeTransfer->getLocaleName();

        if ($localeName === null || !$this->translatorFacade->has($translationKey, $localeName)) {
            return $translationKey;
        }

        return $this->translatorFacade->trans($translationKey, $parameters, null, $localeName);
    }
}
