<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionChecker;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductMeasurementUnitCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductPackagingUnitCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReaderInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Checker
 * @group AddedProductConcreteRestrictionCheckerTest
 */
class AddedProductConcreteRestrictionCheckerTest extends Unit
{
    protected const string SKU = 'service-001-1';

    protected const string SKU_PACKAGED = 'packaged-001-1';

    protected const int ID_PRODUCT_CONCRETE = 11;

    protected const int ID_PRODUCT_CONCRETE_PACKAGED = 22;

    public function testProductViewIsNotRestrictedWithoutPluginsAndWithBothFlagsOff(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testProductViewIsRestrictedWhenAnyPluginRestrictsIt(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [$this->createRestrictionPluginMock(false), $this->createRestrictionPluginMock(true)],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductViewIsNotRestrictedWhenNoPluginRestrictsIt(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [$this->createRestrictionPluginMock(false), $this->createRestrictionPluginMock(false)],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testProductViewIsRestrictedByTheMeasurementUnitCheckerWhenTheFlagIsOn(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            true,
            false,
            $this->createMeasurementUnitCheckerMock(true),
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductViewIsRestrictedByThePackagingUnitCheckerWhenTheFlagIsOn(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            false,
            true,
            null,
            $this->createPackagingUnitCheckerMock(true),
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testMeasurementUnitCheckerIsNotAskedWhenItsFlagIsOff(): void
    {
        // Arrange
        $addedProductMeasurementUnitCheckerMock = $this->createMock(AddedProductMeasurementUnitCheckerInterface::class);
        $addedProductMeasurementUnitCheckerMock->expects($this->never())->method('isRestricted');

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            false,
            true,
            $addedProductMeasurementUnitCheckerMock,
            $this->createPackagingUnitCheckerMock(false),
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testPackagingUnitCheckerIsNotAskedWhenItsFlagIsOff(): void
    {
        // Arrange
        $addedProductPackagingUnitCheckerMock = $this->createMock(AddedProductPackagingUnitCheckerInterface::class);
        $addedProductPackagingUnitCheckerMock->expects($this->never())->method('isRestricted');

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            true,
            false,
            $this->createMeasurementUnitCheckerMock(false),
            $addedProductPackagingUnitCheckerMock,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }

    /**
     * The packaging check costs a storage read per concrete, so a measurement hit must short-circuit it.
     */
    public function testPackagingUnitCheckerIsNotAskedWhenTheMeasurementUnitCheckerAlreadyRestricted(): void
    {
        // Arrange
        $addedProductPackagingUnitCheckerMock = $this->createMock(AddedProductPackagingUnitCheckerInterface::class);
        $addedProductPackagingUnitCheckerMock->expects($this->never())->method('isRestricted');

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            true,
            true,
            $this->createMeasurementUnitCheckerMock(true),
            $addedProductPackagingUnitCheckerMock,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertTrue($isRestricted);
    }

    /**
     * The unit checks own the rule, so a registered plugin must only be consulted after they pass.
     */
    public function testPluginsAreNotAskedWhenAUnitCheckerAlreadyRestricted(): void
    {
        // Arrange
        $addedProductConcreteRestrictionPluginMock = $this->createMock(AddedProductConcreteRestrictionPluginInterface::class);
        $addedProductConcreteRestrictionPluginMock->expects($this->never())->method('isRestricted');

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [$addedProductConcreteRestrictionPluginMock],
            true,
            false,
            $this->createMeasurementUnitCheckerMock(true),
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductViewRestricted(new ProductViewTransfer());

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductConcreteIsResolvedFromStorageBeforeThePluginsAreAsked(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())->setSku(static::SKU);

        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock
            ->expects($this->once())
            ->method('findProductConcreteView')
            ->with(static::SKU)
            ->willReturn($productViewTransfer);

        $addedProductConcreteRestrictionPluginMock = $this->createMock(AddedProductConcreteRestrictionPluginInterface::class);
        $addedProductConcreteRestrictionPluginMock
            ->expects($this->once())
            ->method('isRestricted')
            ->with($productViewTransfer)
            ->willReturn(true);

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $addedProductConcreteViewReaderMock,
            [$addedProductConcreteRestrictionPluginMock],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductConcreteRestricted(static::SKU);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductConcreteMissingFromStorageIsNotRestricted(): void
    {
        // Arrange
        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock->method('findProductConcreteView')->willReturn(null);

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $addedProductConcreteViewReaderMock,
            [$this->createRestrictionPluginMock(true)],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductConcreteRestricted(static::SKU);

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testProductConcreteLookupIsSkippedWithoutPluginsAndWithBothFlagsOff(): void
    {
        // Arrange
        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock->expects($this->never())->method('findProductConcreteView');

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $addedProductConcreteViewReaderMock,
            [],
            false,
            false,
        );

        // Act
        $isRestricted = $addedProductConcreteRestrictionChecker->isProductConcreteRestricted(static::SKU);

        // Assert
        $this->assertFalse($isRestricted);
    }

    /**
     * @return array<string, array<bool>>
     */
    public function anyRestrictionEnabledDataProvider(): array
    {
        return [
            'nothing enabled' => [false, false, false, false],
            'measurement flag on' => [true, false, false, true],
            'packaging flag on' => [false, true, false, true],
            'plugin registered' => [false, false, true, true],
        ];
    }

    /**
     * @dataProvider anyRestrictionEnabledDataProvider
     */
    public function testIsAnyRestrictionEnabledReflectsBothFlagsAndThePluginStack(
        bool $isMeasurementUnitRestricted,
        bool $isPackagingUnitRestricted,
        bool $hasPlugin,
        bool $expectedIsAnyRestrictionEnabled,
    ): void {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            $hasPlugin ? [$this->createRestrictionPluginMock(false)] : [],
            $isMeasurementUnitRestricted,
            $isPackagingUnitRestricted,
        );

        // Act
        $isAnyRestrictionEnabled = $addedProductConcreteRestrictionChecker->isAnyRestrictionEnabled();

        // Assert
        $this->assertSame($expectedIsAnyRestrictionEnabled, $isAnyRestrictionEnabled);
    }

    public function testGetRestrictionsBySkuReadsEveryPackagingUnitInOneRequest(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            false,
            true,
            null,
            $this->createBulkPackagingUnitCheckerMock(
                [static::ID_PRODUCT_CONCRETE_PACKAGED => true, static::ID_PRODUCT_CONCRETE => false],
                1,
            ),
        );

        // Act
        $isRestrictedBySku = $addedProductConcreteRestrictionChecker->getRestrictionsBySku([
            static::SKU_PACKAGED => (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE_PACKAGED),
            static::SKU => (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
        ]);

        // Assert
        $this->assertSame([static::SKU_PACKAGED => true, static::SKU => false], $isRestrictedBySku);
    }

    public function testGetRestrictionsBySkuReturnsNothingWhenNoRestrictionIsEnabled(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            false,
            false,
            null,
            $this->createBulkPackagingUnitCheckerMock([], 0),
        );

        // Act
        $isRestrictedBySku = $addedProductConcreteRestrictionChecker->getRestrictionsBySku([
            static::SKU => (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
        ]);

        // Assert
        $this->assertSame([], $isRestrictedBySku);
    }

    public function testGetRestrictionsBySkuKeepsMeasurementUnitRestrictedProductsOutOfThePackagingUnitRead(): void
    {
        // Arrange: the measurement unit check needs no storage read, so it must decide before the bulk read is built.
        $addedProductPackagingUnitCheckerMock = $this->createMock(AddedProductPackagingUnitCheckerInterface::class);
        $addedProductPackagingUnitCheckerMock
            ->expects($this->once())
            ->method('getRestrictionsByProductConcreteId')
            ->with([])
            ->willReturn([]);

        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [],
            true,
            true,
            $this->createMeasurementUnitCheckerMock(true),
            $addedProductPackagingUnitCheckerMock,
        );

        // Act
        $isRestrictedBySku = $addedProductConcreteRestrictionChecker->getRestrictionsBySku([
            static::SKU => (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
        ]);

        // Assert
        $this->assertSame([static::SKU => true], $isRestrictedBySku);
    }

    public function testGetRestrictionsBySkuFallsBackToThePluginStack(): void
    {
        // Arrange
        $addedProductConcreteRestrictionChecker = $this->createChecker(
            $this->createMock(AddedProductConcreteViewReaderInterface::class),
            [$this->createRestrictionPluginMock(true)],
            false,
            false,
            null,
            $this->createBulkPackagingUnitCheckerMock([], 0),
        );

        // Act
        $isRestrictedBySku = $addedProductConcreteRestrictionChecker->getRestrictionsBySku([
            static::SKU => (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
        ]);

        // Assert
        $this->assertSame([static::SKU => true], $isRestrictedBySku);
    }

    /**
     * @param array<int, bool> $isRestrictedByIdProductConcrete
     */
    protected function createBulkPackagingUnitCheckerMock(
        array $isRestrictedByIdProductConcrete,
        int $expectedCallCount,
    ): AddedProductPackagingUnitCheckerInterface {
        $addedProductPackagingUnitCheckerMock = $this->createMock(AddedProductPackagingUnitCheckerInterface::class);
        $addedProductPackagingUnitCheckerMock
            ->expects($this->exactly($expectedCallCount))
            ->method('getRestrictionsByProductConcreteId')
            ->willReturn($isRestrictedByIdProductConcrete);

        return $addedProductPackagingUnitCheckerMock;
    }

    /**
     * @param array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface> $addedProductConcreteRestrictionPlugins
     */
    protected function createChecker(
        AddedProductConcreteViewReaderInterface $addedProductConcreteViewReader,
        array $addedProductConcreteRestrictionPlugins,
        bool $isMeasurementUnitRestricted,
        bool $isPackagingUnitRestricted,
        ?AddedProductMeasurementUnitCheckerInterface $addedProductMeasurementUnitChecker = null,
        ?AddedProductPackagingUnitCheckerInterface $addedProductPackagingUnitChecker = null,
    ): AddedProductConcreteRestrictionChecker {
        return new AddedProductConcreteRestrictionChecker(
            $addedProductConcreteViewReader,
            $this->createConfigMock($isMeasurementUnitRestricted, $isPackagingUnitRestricted),
            $addedProductMeasurementUnitChecker ?? $this->createMeasurementUnitCheckerMock(false),
            $addedProductPackagingUnitChecker ?? $this->createPackagingUnitCheckerMock(false),
            $addedProductConcreteRestrictionPlugins,
        );
    }

    protected function createConfigMock(
        bool $isMeasurementUnitRestricted,
        bool $isPackagingUnitRestricted,
    ): OrderExperienceManagementConfig {
        $orderExperienceManagementConfigMock = $this->createMock(OrderExperienceManagementConfig::class);
        $orderExperienceManagementConfigMock
            ->method('isMeasurementUnitProductAdditionRestricted')
            ->willReturn($isMeasurementUnitRestricted);
        $orderExperienceManagementConfigMock
            ->method('isPackagingUnitProductAdditionRestricted')
            ->willReturn($isPackagingUnitRestricted);

        return $orderExperienceManagementConfigMock;
    }

    protected function createMeasurementUnitCheckerMock(bool $isRestricted): AddedProductMeasurementUnitCheckerInterface
    {
        $addedProductMeasurementUnitCheckerMock = $this->createMock(AddedProductMeasurementUnitCheckerInterface::class);
        $addedProductMeasurementUnitCheckerMock->method('isRestricted')->willReturn($isRestricted);

        return $addedProductMeasurementUnitCheckerMock;
    }

    protected function createPackagingUnitCheckerMock(bool $isRestricted): AddedProductPackagingUnitCheckerInterface
    {
        $addedProductPackagingUnitCheckerMock = $this->createMock(AddedProductPackagingUnitCheckerInterface::class);
        $addedProductPackagingUnitCheckerMock->method('isRestricted')->willReturn($isRestricted);

        return $addedProductPackagingUnitCheckerMock;
    }

    protected function createRestrictionPluginMock(bool $isRestricted): AddedProductConcreteRestrictionPluginInterface
    {
        $addedProductConcreteRestrictionPluginMock = $this->createMock(AddedProductConcreteRestrictionPluginInterface::class);
        $addedProductConcreteRestrictionPluginMock->method('isRestricted')->willReturn($isRestricted);

        return $addedProductConcreteRestrictionPluginMock;
    }
}
