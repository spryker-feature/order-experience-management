<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\ByDaysCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group UpdateRecurringScheduleCollectionTest
 */
class UpdateRecurringScheduleCollectionTest extends Unit
{
    protected const string UPDATED_NAME = 'Updated recurring order name';

    protected const string EXISTING_NAME = 'Existing recurring order name';

    protected const string UPDATED_CADENCE_TYPE = SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS;

    protected const int UPDATED_CADENCE_VALUE = 3;

    protected const string UPDATED_NEXT_TRIGGER_DATE = '2026-11-20';

    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string UPDATED_PRICE_MODE = 'GROSS_MODE';

    protected const string GROUP_KEY_A = 'group-key-a';

    protected const string GROUP_KEY_B = 'group-key-b';

    /**
     * @uses \SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper::OVERRIDE_BUILD_ITEM_DATA
     */
    protected const string OVERRIDE_BUILD_ITEM_DATA = 'build_item_data';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [
            new WeeklyCadenceTypePlugin(),
            new BiWeeklyCadenceTypePlugin(),
            new MonthlyCadenceTypePlugin(),
            new EveryNWeeksCadenceTypePlugin(),
            new ByDaysCadenceTypePlugin(),
        ]);
    }

    public function testUpdatesNameCadenceAndNextTriggerDate(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME)
            ->setCadenceType(static::UPDATED_CADENCE_TYPE)
            ->setCadenceValue(static::UPDATED_CADENCE_VALUE)
            ->setNextTriggerDate(static::UPDATED_NEXT_TRIGGER_DATE));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getRecurringSchedules());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(static::UPDATED_NAME, $recurringScheduleEntity->getName());
        $this->assertSame(static::UPDATED_CADENCE_TYPE, $recurringScheduleEntity->getCadenceType());
        $this->assertSame(static::UPDATED_CADENCE_VALUE, $recurringScheduleEntity->getCadenceValue());
        $this->assertSame(
            static::UPDATED_NEXT_TRIGGER_DATE,
            $recurringScheduleEntity->getNextTriggerDate()->format(static::DATE_FORMAT),
        );
    }

    /**
     * @dataProvider editableStatusDataProvider
     */
    public function testUpdatesScheduleInAnyStatus(string $status): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => $status,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(static::UPDATED_NAME, $recurringScheduleEntity->getName());
    }

    public function testMergesQuoteOverrideIntoStoredQuoteData(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $quoteOverrideTransfer = (new QuoteTransfer())
            ->setPriceMode(static::UPDATED_PRICE_MODE);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setQuote($quoteOverrideTransfer));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $decodedQuoteData = json_decode((string)$recurringScheduleEntity->getQuoteData(), true);

        $this->assertSame(static::UPDATED_PRICE_MODE, $decodedQuoteData[QuoteTransfer::PRICE_MODE] ?? null);
    }

    public function testReturnsErrorWhenScheduleNotFound(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid('00000000-0000-0000-0000-000000000000')
            ->setName(static::UPDATED_NAME));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
    }

    public function testReturnsErrorAndDoesNotMutateWhenScheduleBelongsToAnotherCustomer(): void
    {
        // Arrange
        $idOwnerCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $idOtherCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idOwnerCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);
        $originalName = $recurringScheduleTransfer->getName();

        $requestTransfer = $this->createRequest($idOtherCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame($originalName, $recurringScheduleEntity->getName());
    }

    public function testReturnsEmptyResponseWhenNoSchedulesRequested(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $requestTransfer = (new RecurringScheduleCollectionRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setIdCustomer($idCustomer));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
    }

    public function testUpdatesPriceToleranceAndLeavesOtherFieldsUntouched(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::NAME => static::EXISTING_NAME,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent('2.5'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame('2.5', $this->readTolerance($recurringScheduleEntity->getPriceTolerancePercent()));
        $this->assertSame(static::EXISTING_NAME, $recurringScheduleEntity->getName());
    }

    public function testAcceptsZeroAndTheUpperBoundAsPriceTolerance(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '3',
        ]);

        // Act
        $zeroResponseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent('0')));
        $zeroTolerance = SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent();

        $maxResponseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent(SharedOrderExperienceManagementConfig::PRICE_TOLERANCE_PERCENT_MAX)));
        $maxTolerance = SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent();

        // Assert
        $this->assertCount(0, $zeroResponseTransfer->getErrors());
        $this->assertSame('0', $this->readTolerance($zeroTolerance));
        $this->assertCount(0, $maxResponseTransfer->getErrors());
        $this->assertSame('999.99', $this->readTolerance($maxTolerance));
    }

    /**
     * @dataProvider outOfRangePriceToleranceDataProvider
     */
    public function testRejectsPriceToleranceOutsideTheAllowedRange(string $priceTolerancePercent): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '1',
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent($priceTolerancePercent));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('recurring_orders.price_tolerance.error.out_of_range', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame($recurringScheduleTransfer->getUuidOrFail(), $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame('1', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent()));
    }

    public function testRejectsPriceToleranceChangeWhileScheduleAwaitsReview(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '1',
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME)
            ->setPriceTolerancePercent('5'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert: the whole request for that schedule is refused, so the name is not half-applied either
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('recurring_orders.price_tolerance.locked_notice', $responseTransfer->getErrors()->offsetGet(0)->getMessage());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame('1', $this->readTolerance($recurringScheduleEntity->getPriceTolerancePercent()));
        $this->assertNotSame(static::UPDATED_NAME, $recurringScheduleEntity->getName());
    }

    public function testKeepsPriceToleranceWhenTheRequestDoesNotCarryOne(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '1.75',
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME));

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertSame('1.75', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent()));
    }

    public function testAcceptsTheSmallestStepOfOneHundredthOfAPercent(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent('0.01'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame('0.01', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent()));
    }

    public function testRejectsAToleranceWithMoreDecimalsThanTheStoredScale(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '1',
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent('0.001'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('recurring_orders.price_tolerance.error.too_precise', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function outOfRangePriceToleranceDataProvider(): array
    {
        return [
            'negative' => ['-0.01'],
            'above the maximum' => ['1000'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $itemOverridesList
     *
     * @return array{0: int, 1: string, 2: int} [idCustomer, uuid, idRecurringSchedule]
     */
    protected function haveScheduleWithItems(string $status, array $itemOverridesList): array
    {
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => $status]);

        foreach ($itemOverridesList as $itemOverrides) {
            $this->tester->haveRecurringScheduleItem(
                $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
                $itemOverrides + [
                    RecurringScheduleItemTransfer::SKU => 'sku-' . ($itemOverrides[RecurringScheduleItemTransfer::GROUP_KEY] ?? 'x'),
                    RecurringScheduleItemTransfer::QUANTITY => 1,
                    RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                    static::OVERRIDE_BUILD_ITEM_DATA => true,
                ],
            );
        }

        return [$idCustomer, $recurringScheduleTransfer->getUuidOrFail(), $recurringScheduleTransfer->getIdRecurringScheduleOrFail()];
    }

    protected function createItemTolerance(string $groupKey, ?string $priceTolerancePercent): RecurringScheduleItemTransfer
    {
        return (new RecurringScheduleItemTransfer())->setGroupKey($groupKey)->setPriceTolerancePercent($priceTolerancePercent);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $itemTransfers
     */
    protected function createItemToleranceRequest(int $idCustomer, string $uuid, array $itemTransfers): RecurringScheduleCollectionRequestTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setUuid($uuid);

        foreach ($itemTransfers as $itemTransfer) {
            $recurringScheduleTransfer->addItem($itemTransfer);
        }

        return $this->createRequest($idCustomer, $recurringScheduleTransfer);
    }

    /**
     * @return array<?string> The stored tolerance of every row of the group, in id order.
     */
    protected function readItemTolerances(int $idRecurringSchedule, string $groupKey): array
    {
        $tolerances = [];

        foreach (SpyRecurringScheduleItemQuery::create()->filterByFkRecurringSchedule($idRecurringSchedule)->filterByGroupKey($groupKey)->orderByIdRecurringScheduleItem()->find() as $recurringScheduleItemEntity) {
            $tolerance = $recurringScheduleItemEntity->getPriceTolerancePercent();
            $tolerances[] = $tolerance === null ? null : $this->readTolerance($tolerance);
        }

        return $tolerances;
    }

    protected function readTolerance(string $storedTolerance): string
    {
        return Decimal::create($storedTolerance)->trim()->toString();
    }

    public function testUpdatesEveryRowOfAProductGroupAndLeavesOtherGroupsUntouched(): void
    {
        // Arrange: product A is stored as two rows, product B as one
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, '5')]),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(['5', '5'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_B));
    }

    public function testAppliesDifferentTolerancesToSeveralProductsInOneRequest(): void
    {
        // Arrange: A and C get the same value, B another, D stays untouched
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
            [RecurringScheduleItemTransfer::GROUP_KEY => 'group-c'],
            [RecurringScheduleItemTransfer::GROUP_KEY => 'group-d'],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [
                $this->createItemTolerance(static::GROUP_KEY_A, '2.5'),
                $this->createItemTolerance(static::GROUP_KEY_B, '10'),
                $this->createItemTolerance('group-c', '2.5'),
            ]),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(['2.5'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
        $this->assertSame(['10'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_B));
        $this->assertSame(['2.5'], $this->readItemTolerances($idRecurringSchedule, 'group-c'));
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, 'group-d'));
    }

    public function testResetsAnItemToleranceToZeroWhenItIsSentAsNull(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A, RecurringScheduleItemTransfer::PRICE_TOLERANCE_PERCENT => '5'],
        ]);

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, null)]),
        );

        // Assert
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
    }

    public function testStoresAnExplicitZero(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, '0')]),
        );

        // Assert
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
    }

    public function testLeavesAnItemUntouchedWhenItsToleranceIsNotSent(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A, RecurringScheduleItemTransfer::PRICE_TOLERANCE_PERCENT => '5'],
        ]);

        $requestTransfer = $this->createItemToleranceRequest($idCustomer, $uuid, [(new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A)]);
        $requestTransfer->getRecurringSchedules()->offsetGet(0)->setName(static::UPDATED_NAME);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(['5'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
    }

    public function testAppliesTheOrderAndTheItemTolerancesTogether(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $requestTransfer = $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, '3')]);
        $requestTransfer->getRecurringSchedules()->offsetGet(0)->setPriceTolerancePercent('2');

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertSame(['3'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
        $this->assertSame('2', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule)->getPriceTolerancePercent()));
    }

    /**
     * @dataProvider invalidItemToleranceDataProvider
     */
    public function testRefusesTheWholeUpdateWhenAnItemToleranceIsInvalid(string $value, string $expectedError): void
    {
        // Arrange: the order tolerance in the same request is valid and must not be applied either
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A, RecurringScheduleItemTransfer::PRICE_TOLERANCE_PERCENT => '1'],
        ]);
        $requestTransfer = $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, $value)]);
        $requestTransfer->getRecurringSchedules()->offsetGet(0)->setPriceTolerancePercent('4');

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame($expectedError, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(['1'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
        $this->assertSame('0', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule)->getPriceTolerancePercent()));
    }

    public function testRefusesAnItemThatIsNotPartOfTheSchedule(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance('does-not-exist', '5')]),
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('recurring_orders.price_tolerance.error.item_not_found', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
    }

    public function testRefusesItemToleranceChangesWhileTheScheduleAwaitsReview(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [$this->createItemTolerance(static::GROUP_KEY_A, '5')]),
        );

        // Assert
        $this->assertSame('recurring_orders.price_tolerance.locked_notice', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(['0'], $this->readItemTolerances($idRecurringSchedule, static::GROUP_KEY_A));
    }

    public function testUpdatesABundleParentByItsBundleItemIdentifier(): void
    {
        // Arrange: a bundle parent has no group key
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => 'bundle-sku',
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 1000,
            RecurringScheduleItemTransfer::BUNDLE_ITEM_IDENTIFIER => 'bundle-1',
            static::OVERRIDE_BUILD_ITEM_DATA => true,
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createItemToleranceRequest($idCustomer, $uuid, [(new RecurringScheduleItemTransfer())->setBundleItemIdentifier('bundle-1')->setPriceTolerancePercent('7')]),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $bundleRow = SpyRecurringScheduleItemQuery::create()->filterByFkRecurringSchedule($idRecurringSchedule)->filterByBundleItemIdentifier('bundle-1')->findOne();
        $this->assertSame('7', $this->readTolerance($bundleRow->getPriceTolerancePercent()));
    }

    public function testStoresTheIgnoreSettingWhenTheOrderToleranceIsOn(): void
    {
        // Arrange
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, []);
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())->setUuid($uuid)->setPriceTolerancePercent('10')->setIsProductPriceChangeIgnored(true));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule);
        $this->assertTrue($recurringScheduleEntity->getIsProductPriceChangeIgnored());
        $this->assertSame('10', $this->readTolerance($recurringScheduleEntity->getPriceTolerancePercent()));
    }

    public function testRefusesTheIgnoreSettingWithoutAnOrderTolerance(): void
    {
        // Arrange: nothing stored, nothing requested: both are 0
        [$idCustomer, $uuid, $idRecurringSchedule] = $this->haveScheduleWithItems(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, []);
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())->setUuid($uuid)->setIsProductPriceChangeIgnored(true));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertSame('recurring_orders.price_tolerance.error.ignore_requires_order_tolerance', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertFalse(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule)->getIsProductPriceChangeIgnored());
    }

    public function testRefusesSwitchingTheOrderToleranceOffWhileProductsAreIgnored(): void
    {
        // Arrange: stored 10% with products ignored
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '10',
            RecurringScheduleTransfer::IS_PRODUCT_PRICE_CHANGE_IGNORED => true,
        ]);
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())->setUuid($recurringScheduleTransfer->getUuidOrFail())->setPriceTolerancePercent('0'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert: it would leave no price check at all
        $this->assertSame('recurring_orders.price_tolerance.error.ignore_requires_order_tolerance', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame('10', $this->readTolerance(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getPriceTolerancePercent()));
    }

    public function testAcceptsSwitchingTheOrderToleranceOffTogetherWithTheIgnoreSetting(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '10',
            RecurringScheduleTransfer::IS_PRODUCT_PRICE_CHANGE_IGNORED => true,
        ]);
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setPriceTolerancePercent('0')
            ->setIsProductPriceChangeIgnored(false));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertFalse(SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())->getIsProductPriceChangeIgnored());
    }

    public function testRefusesTheIgnoreSettingWhileTheScheduleAwaitsReview(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => '10',
        ]);
        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())->setUuid($recurringScheduleTransfer->getUuidOrFail())->setIsProductPriceChangeIgnored(true));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertSame('recurring_orders.price_tolerance.locked_notice', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function invalidItemToleranceDataProvider(): array
    {
        return [
            'negative' => ['-0.01', 'recurring_orders.price_tolerance.error.out_of_range'],
            'above the maximum' => ['1000', 'recurring_orders.price_tolerance.error.out_of_range'],
            'more decimals than stored' => ['0.001', 'recurring_orders.price_tolerance.error.too_precise'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function editableStatusDataProvider(): array
    {
        return [
            'active' => [SharedOrderExperienceManagementConfig::STATUS_ACTIVE],
            'paused' => [SharedOrderExperienceManagementConfig::STATUS_PAUSED],
            'cancelled' => [SharedOrderExperienceManagementConfig::STATUS_CANCELLED],
            'review required' => [SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED],
            'failed' => [SharedOrderExperienceManagementConfig::STATUS_FAILED],
        ];
    }

    public function testUpdatesCadenceOptionsAndReadsThemBack(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions(['1', '4']));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByUuid($recurringScheduleTransfer->getUuidOrFail());

        $this->assertSame(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            $recurringScheduleEntity->getCadenceType(),
        );
        $this->assertSame('["1","4"]', $recurringScheduleEntity->getCadenceOptions());
        $this->assertNull($recurringScheduleEntity->getCadenceValue());

        $this->assertSame(['1', '4'], $responseTransfer->getRecurringSchedules()->offsetGet(0)->getCadenceOptions());
    }

    public function testSwitchingAwayFromByDaysClearsCadenceOptions(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
                ->setUuid($recurringScheduleTransfer->getUuidOrFail())
                ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
                ->setCadenceOptions(['1', '4'])),
        );

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection(
            $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
                ->setUuid($recurringScheduleTransfer->getUuidOrFail())
                ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY)),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByUuid($recurringScheduleTransfer->getUuidOrFail());

        $this->assertSame(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            $recurringScheduleEntity->getCadenceType(),
        );
        $this->assertNull($recurringScheduleEntity->getCadenceOptions());
    }

    public function testReturnsCadenceValueRequiredErrorWhenEveryNWeeksIntervalIsMissing(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME)
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS)
            ->setCadenceValue(null));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
        $this->assertSame(
            'recurring_orders.cadence.validation.value_required',
            $responseTransfer->getErrors()->offsetGet(0)->getMessage(),
        );
    }

    public function testDoesNotPersistScheduleWhenEveryNWeeksIntervalIsMissing(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setName(static::UPDATED_NAME)
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS)
            ->setCadenceValue(null));

        // Act
        $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertNotNull($recurringScheduleEntity);
        $this->assertSame($recurringScheduleTransfer->getName(), $recurringScheduleEntity->getName());
    }

    public function testReturnsDaysRequiredErrorWhenByDaysSelectionIsMissing(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = $this->createRequest($idCustomer, (new RecurringScheduleTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions([]));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
        $this->assertSame(
            'recurring_orders.cadence.validation.days_required',
            $responseTransfer->getErrors()->offsetGet(0)->getMessage(),
        );
    }

    protected function createRequest(
        int $idCustomer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): RecurringScheduleCollectionRequestTransfer {
        return (new RecurringScheduleCollectionRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setIdCustomer($idCustomer))
            ->addRecurringSchedule($recurringScheduleTransfer);
    }
}
