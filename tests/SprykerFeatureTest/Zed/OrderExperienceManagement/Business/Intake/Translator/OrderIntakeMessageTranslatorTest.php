<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Translator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\LocaleTransfer;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Translator
 * @group OrderIntakeMessageTranslatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeMessageTranslatorTest extends Unit
{
    protected const string GLOSSARY_KEY = 'checkout.error.address.invalid';

    protected const string TRANSLATED_MESSAGE = 'Die Adresse ist ungültig.';

    protected const string LOCALE_NAME_DE_DE = 'de_DE';

    protected const string LOCALE_NAME_EN_US = 'en_US';

    protected const string VALIDATION_ISSUE_MESSAGE = 'Product with SKU "%sku%" was not found.';

    protected const string TRANSLATED_VALIDATION_ISSUE_MESSAGE = 'Produkt mit SKU "131_24872891" wurde nicht gefunden.';

    public function testResolveLocaleUsesTheGivenLocaleWhenItIsKnown(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('hasLocale')->with(static::LOCALE_NAME_DE_DE)->willReturn(true);
        $localeFacadeMock->method('getLocale')->with(static::LOCALE_NAME_DE_DE)->willReturn($localeTransfer);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);

        // Act
        $resolvedLocaleTransfer = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->resolveLocale(static::LOCALE_NAME_DE_DE);

        // Assert
        $this->assertSame($localeTransfer, $resolvedLocaleTransfer);
    }

    public function testTranslateCheckoutErrorUsesTheGivenLocale(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);
        $glossaryFacadeMock->method('hasTranslation')->with(static::GLOSSARY_KEY, $localeTransfer)->willReturn(true);
        $glossaryFacadeMock->method('translate')
            ->with(static::GLOSSARY_KEY, [], $localeTransfer)
            ->willReturn(static::TRANSLATED_MESSAGE);

        // Act
        $translatedMessage = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->translateCheckoutError(static::GLOSSARY_KEY, [], $localeTransfer);

        // Assert
        $this->assertSame(static::TRANSLATED_MESSAGE, $translatedMessage);
    }

    public function testTranslateCheckoutErrorForwardsParametersToGlossaryFacade(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);
        $parameters = ['{{threshold}}' => '€50.00'];

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);
        $glossaryFacadeMock->method('hasTranslation')->willReturn(true);
        $glossaryFacadeMock->expects($this->once())
            ->method('translate')
            ->with(static::GLOSSARY_KEY, $parameters, $localeTransfer)
            ->willReturn('Sie sollten Waren im Wert von €50.00 hinzufügen.');

        // Act
        $translatedMessage = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->translateCheckoutError(static::GLOSSARY_KEY, $parameters, $localeTransfer);

        // Assert
        $this->assertSame('Sie sollten Waren im Wert von €50.00 hinzufügen.', $translatedMessage);
    }

    /**
     * The one contract the ticket cares about: an unknown or missing locale name never blocks
     * resolution — it falls back to English rather than propagating a lookup failure.
     *
     * @dataProvider provideUnknownOrMissingLocaleNames
     */
    public function testResolveLocaleFallsBackToEnglishWhenLocaleIsUnknownOrMissing(?string $localeName): void
    {
        // Arrange
        $englishLocaleTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_EN_US);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('hasLocale')
            ->willReturnCallback(static fn (string $localeName): bool => $localeName === static::LOCALE_NAME_EN_US);
        $localeFacadeMock->expects($this->once())
            ->method('getLocale')
            ->with(static::LOCALE_NAME_EN_US)
            ->willReturn($englishLocaleTransfer);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);

        // Act
        $resolvedLocaleTransfer = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->resolveLocale($localeName);

        // Assert
        $this->assertSame($englishLocaleTransfer, $resolvedLocaleTransfer);
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public function provideUnknownOrMissingLocaleNames(): iterable
    {
        yield 'null locale name' => [null];
        yield 'unknown locale name' => ['fr_FR'];
    }

    public function testTranslateCheckoutErrorReturnsTheRawKeyWhenNoTranslationExists(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);
        $glossaryFacadeMock->method('hasTranslation')->willReturn(false);
        $glossaryFacadeMock->expects($this->never())->method('translate');

        // Act
        $translatedMessage = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->translateCheckoutError(static::GLOSSARY_KEY, [], $localeTransfer);

        // Assert
        $this->assertSame(static::GLOSSARY_KEY, $translatedMessage);
    }

    public function testTranslateValidationIssueTranslatesThroughTheTranslatorFacade(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);
        $parameters = ['%sku%' => '131_24872891'];

        $translatorFacadeMock = $this->createMock(TranslatorFacadeInterface::class);
        $translatorFacadeMock->method('has')->with(static::VALIDATION_ISSUE_MESSAGE, static::LOCALE_NAME_DE_DE)->willReturn(true);
        $translatorFacadeMock->expects($this->once())
            ->method('trans')
            ->with(static::VALIDATION_ISSUE_MESSAGE, $parameters, null, static::LOCALE_NAME_DE_DE)
            ->willReturn(static::TRANSLATED_VALIDATION_ISSUE_MESSAGE);

        // Act
        $translatedMessage = $this->createTranslator(
            $this->createMock(GlossaryFacadeInterface::class),
            $this->createMock(LocaleFacadeInterface::class),
            $translatorFacadeMock,
        )->translateValidationIssue(static::VALIDATION_ISSUE_MESSAGE, $parameters, $localeTransfer);

        // Assert
        $this->assertSame(static::TRANSLATED_VALIDATION_ISSUE_MESSAGE, $translatedMessage);
    }

    public function testTranslateValidationIssueReturnsTheRawMessageWhenNoTranslationExists(): void
    {
        // Arrange
        $localeTransfer = (new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE);

        $translatorFacadeMock = $this->createMock(TranslatorFacadeInterface::class);
        $translatorFacadeMock->method('has')->willReturn(false);
        $translatorFacadeMock->expects($this->never())->method('trans');

        // Act
        $translatedMessage = $this->createTranslator(
            $this->createMock(GlossaryFacadeInterface::class),
            $this->createMock(LocaleFacadeInterface::class),
            $translatorFacadeMock,
        )->translateValidationIssue(static::VALIDATION_ISSUE_MESSAGE, [], $localeTransfer);

        // Assert
        $this->assertSame(static::VALIDATION_ISSUE_MESSAGE, $translatedMessage);
    }

    protected function createTranslator(
        GlossaryFacadeInterface $glossaryFacadeMock,
        LocaleFacadeInterface $localeFacadeMock,
        ?TranslatorFacadeInterface $translatorFacadeMock = null,
    ): OrderIntakeMessageTranslator {
        return new OrderIntakeMessageTranslator(
            $glossaryFacadeMock,
            $translatorFacadeMock ?? $this->createMock(TranslatorFacadeInterface::class),
            $localeFacadeMock,
            $this->createConfig(),
        );
    }

    protected function createConfig(): OrderExperienceManagementConfig
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getFallbackLocaleName')->willReturn(static::LOCALE_NAME_EN_US);

        return $configMock;
    }

    public function testResolveLocaleFallsBackToTheConfiguredNameWhenNoLocaleIsPersisted(): void
    {
        // Arrange
        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('hasLocale')->willReturn(false);
        $localeFacadeMock->expects($this->never())->method('getLocale');
        $localeFacadeMock->expects($this->never())->method('getCurrentLocale');

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);

        // Act
        $resolvedLocaleTransfer = ($this->createTranslator($glossaryFacadeMock, $localeFacadeMock))
            ->resolveLocale(null);

        // Assert
        $this->assertSame(static::LOCALE_NAME_EN_US, $resolvedLocaleTransfer->getLocaleName());
    }

    public function testTranslateCheckoutErrorReturnsTheKeyWhenTheFallbackLocaleIsNotPersisted(): void
    {
        // Arrange
        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('hasLocale')->willReturn(false);

        $glossaryFacadeMock = $this->createMock(GlossaryFacadeInterface::class);
        $glossaryFacadeMock->method('hasTranslation')->willReturn(false);
        $glossaryFacadeMock->expects($this->never())->method('translate');

        $errorTranslator = $this->createTranslator($glossaryFacadeMock, $localeFacadeMock);

        // Act
        $translatedMessage = $errorTranslator->translateCheckoutError(
            static::GLOSSARY_KEY,
            [],
            $errorTranslator->resolveLocale(null),
        );

        // Assert
        $this->assertSame(static::GLOSSARY_KEY, $translatedMessage);
    }
}
