<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator;

use Generated\Shared\Transfer\LocaleTransfer;

interface OrderIntakeMessageTranslatorInterface
{
    public function resolveLocale(?string $localeName): LocaleTransfer;

    /**
     * Translates a checkout error glossary key, falling back to the key itself when no translation exists.
     *
     * @param array<string, mixed> $parameters
     */
    public function translateCheckoutError(string $glossaryKey, array $parameters, LocaleTransfer $localeTransfer): string;

    /**
     * Translates a validation issue message, falling back to the message itself when no translation exists.
     *
     * @param array<string, mixed> $parameters
     */
    public function translateValidationIssue(string $translationKey, array $parameters, LocaleTransfer $localeTransfer): string;
}
