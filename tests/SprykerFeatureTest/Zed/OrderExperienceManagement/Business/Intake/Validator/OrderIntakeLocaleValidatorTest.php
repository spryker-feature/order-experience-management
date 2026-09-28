<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeLocaleValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeLocaleValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeLocaleValidatorTest extends Unit
{
    protected const string LOCALE_NAME = 'de_DE';

    /**
     * An unknown locale would otherwise reach `LocaleFacade::getLocale()` unvalidated inside
     * `OrderIntakeWriter::applyOrderLocale()`, which throws — a 500 for a typo'd locale name, raised
     * well after the rest of the payload was already accepted.
     */
    public function testValidateLocaleReportsAnUnknownLocale(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator(false)->validateLocale(
            (new OrderIntakeRequestTransfer())->setOrderLocaleName('xx_XX'),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('locale', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
        $this->assertSame('xx_XX', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%localeName%']);
    }

    public function testValidateLocaleAcceptsAKnownLocale(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator(true)->validateLocale(
            (new OrderIntakeRequestTransfer())->setOrderLocaleName(static::LOCALE_NAME),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * `locale` is optional on POST — omitting it is not a validation problem, it means "place the
     * order in whatever locale checkout resolves by default."
     *
     * @dataProvider provideAbsentLocaleNames
     */
    public function testValidateLocaleIgnoresAnAbsentLocaleName(?string $localeName): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->expects($this->never())->method('hasLocale');

        // Act
        (new OrderIntakeLocaleValidator($localeFacadeMock))->validateLocale(
            (new OrderIntakeRequestTransfer())->setOrderLocaleName($localeName),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * @return array<string, array{string|null}>
     */
    public function provideAbsentLocaleNames(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ['   '],
        ];
    }

    protected function createValidator(bool $hasLocale): OrderIntakeLocaleValidator
    {
        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('hasLocale')->willReturn($hasLocale);

        return new OrderIntakeLocaleValidator($localeFacadeMock);
    }
}
