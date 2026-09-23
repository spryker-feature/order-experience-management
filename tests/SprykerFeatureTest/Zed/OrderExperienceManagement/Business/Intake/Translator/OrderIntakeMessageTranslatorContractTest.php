<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Translator;

use Codeception\Test\Unit;
use Spryker\Zed\Glossary\Business\GlossaryFacade;
use Spryker\Zed\Locale\Business\LocaleFacade;
use Spryker\Zed\Translator\Business\TranslatorFacade;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Exercises the translator against the REAL Glossary and Locale facades rather than mocks.
 *
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Translator
 * @group OrderIntakeMessageTranslatorContractTest
 * Add your own group annotations below this line
 */
class OrderIntakeMessageTranslatorContractTest extends Unit
{
    protected const string GLOSSARY_KEY = 'checkout.error.contract_test.address_invalid';

    protected const string LOCALE_NAME = 'de_DE';

    protected const string TRANSLATED_MESSAGE = 'Die Adresse ist ungültig.';

    protected const string PARAMETERIZED_GLOSSARY_KEY = 'sales-order-threshold.contract_test.hard-minimum-threshold';

    public function testTranslateReturnsTheRealTranslationSeededForTheGivenLocale(): void
    {
        // Arrange
        $localeFacade = new LocaleFacade();
        $glossaryFacade = new GlossaryFacade();

        if (!$localeFacade->hasLocale(static::LOCALE_NAME)) {
            $localeFacade->createLocale(static::LOCALE_NAME);
        }

        $glossaryFacade->createKey(static::GLOSSARY_KEY);
        $glossaryFacade->createTranslation(
            static::GLOSSARY_KEY,
            $localeFacade->getLocale(static::LOCALE_NAME),
            static::TRANSLATED_MESSAGE,
        );

        $errorTranslator = new OrderIntakeMessageTranslator($glossaryFacade, new TranslatorFacade(), $localeFacade, new OrderExperienceManagementConfig());
        $localeTransfer = $errorTranslator->resolveLocale(static::LOCALE_NAME);

        // Act
        $translatedMessage = $errorTranslator->translateCheckoutError(static::GLOSSARY_KEY, [], $localeTransfer);

        // Assert
        $this->assertSame(static::TRANSLATED_MESSAGE, $translatedMessage);
    }

    public function testTranslateSubstitutesParametersIntoTheRealTranslation(): void
    {
        // Arrange
        $localeFacade = new LocaleFacade();
        $glossaryFacade = new GlossaryFacade();

        if (!$localeFacade->hasLocale(static::LOCALE_NAME)) {
            $localeFacade->createLocale(static::LOCALE_NAME);
        }

        $glossaryFacade->createKey(static::PARAMETERIZED_GLOSSARY_KEY);
        $glossaryFacade->createTranslation(
            static::PARAMETERIZED_GLOSSARY_KEY,
            $localeFacade->getLocale(static::LOCALE_NAME),
            'Sie sollten Waren im Wert von {{threshold}} hinzufügen.',
        );

        $errorTranslator = new OrderIntakeMessageTranslator($glossaryFacade, new TranslatorFacade(), $localeFacade, new OrderExperienceManagementConfig());
        $localeTransfer = $errorTranslator->resolveLocale(static::LOCALE_NAME);

        // Act
        $translatedMessage = $errorTranslator->translateCheckoutError(
            static::PARAMETERIZED_GLOSSARY_KEY,
            ['{{threshold}}' => '€50.00'],
            $localeTransfer,
        );

        // Assert
        $this->assertSame('Sie sollten Waren im Wert von €50.00 hinzufügen.', $translatedMessage);
    }

    public function testTranslateReturnsTheRawKeyWhenNoRealTranslationWasEverSeeded(): void
    {
        // Arrange
        $localeFacade = new LocaleFacade();
        $glossaryFacade = new GlossaryFacade();

        if (!$localeFacade->hasLocale(static::LOCALE_NAME)) {
            $localeFacade->createLocale(static::LOCALE_NAME);
        }

        $errorTranslator = new OrderIntakeMessageTranslator($glossaryFacade, new TranslatorFacade(), $localeFacade, new OrderExperienceManagementConfig());
        $localeTransfer = $errorTranslator->resolveLocale(static::LOCALE_NAME);

        // Act
        $translatedMessage = $errorTranslator->translateCheckoutError('checkout.error.contract_test.never_seeded', [], $localeTransfer);

        // Assert
        $this->assertSame('checkout.error.contract_test.never_seeded', $translatedMessage);
    }

    public function testResolveLocaleFallsBackToEnglishWhenTheGivenLocaleWasNeverCreated(): void
    {
        // Arrange
        $localeFacade = new LocaleFacade();
        $glossaryFacade = new GlossaryFacade();

        $errorTranslator = new OrderIntakeMessageTranslator($glossaryFacade, new TranslatorFacade(), $localeFacade, new OrderExperienceManagementConfig());

        // Act
        $localeTransfer = $errorTranslator->resolveLocale('zz_ZZ');

        // Assert — falling back to English does not throw MissingLocaleException, and since no
        // translation was seeded for English either, translating with the resolved locale returns
        // the raw key.
        $translatedMessage = $errorTranslator->translateCheckoutError(static::GLOSSARY_KEY, [], $localeTransfer);
        $this->assertSame(static::GLOSSARY_KEY, $translatedMessage);
    }
}
