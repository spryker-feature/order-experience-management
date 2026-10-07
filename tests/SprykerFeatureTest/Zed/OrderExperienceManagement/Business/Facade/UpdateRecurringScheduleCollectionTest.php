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
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
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

    protected const string UPDATED_CADENCE_TYPE = SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS;

    protected const int UPDATED_CADENCE_VALUE = 3;

    protected const string UPDATED_NEXT_TRIGGER_DATE = '2026-11-20';

    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string UPDATED_PRICE_MODE = 'GROSS_MODE';

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
