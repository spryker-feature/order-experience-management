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
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeStoreValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeStoreValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeStoreValidatorTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    /**
     * A store that does not exist used to surface as `Store with name "XX" not found!` — a 500 from
     * whichever step first asked the platform to resolve it, long after intake had accepted the
     * payload. It is a payload problem and belongs with the other lookup checks.
     */
    public function testValidateStoreReportsAnUnknownStore(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator(null)->validateStore(
            (new OrderIntakeRequestTransfer())->setStoreName('XX'),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('store', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
        $this->assertSame('XX', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%storeName%']);
    }

    public function testValidateStoreAcceptsAKnownStore(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator((new StoreTransfer())->setName(static::STORE_NAME))->validateStore(
            (new OrderIntakeRequestTransfer())->setStoreName(static::STORE_NAME),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * `orders.validation.yml` declares `store` NotBlank and the Symfony validator enforces it before
     * the processor runs, so an absent one never reaches here. Reporting it a second time would put
     * two issues on one field; the lookup simply has nothing to do.
     *
     * @dataProvider provideAbsentStoreNames
     */
    public function testValidateStoreIgnoresAnAbsentStoreName(?string $storeName): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $storeFacadeMock = $this->createMock(StoreFacadeInterface::class);
        $storeFacadeMock->expects($this->never())->method('findStoreByName');

        // Act
        (new OrderIntakeStoreValidator($storeFacadeMock))->validateStore(
            (new OrderIntakeRequestTransfer())->setStoreName($storeName),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * @return array<string, array{string|null}>
     */
    public function provideAbsentStoreNames(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ['   '],
        ];
    }

    protected function createValidator(?StoreTransfer $storeTransfer): OrderIntakeStoreValidator
    {
        $storeFacadeMock = $this->createMock(StoreFacadeInterface::class);
        $storeFacadeMock->method('findStoreByName')->willReturn($storeTransfer);

        return new OrderIntakeStoreValidator($storeFacadeMock);
    }
}
