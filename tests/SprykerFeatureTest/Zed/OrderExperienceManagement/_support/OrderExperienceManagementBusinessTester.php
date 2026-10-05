<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\CartChangeTransfer;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator\PriceScheduleValidatorPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\Stub\FixedScheduleValidatorPlugin;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \Generated\Shared\Transfer\CompanyTransfer haveCompany(array $seedData = [])
 * @method \Generated\Shared\Transfer\PermissionTransfer havePermission(\Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface $permissionPlugin)
 * @method void preparePermissionStorageDependency(\Spryker\Zed\PermissionExtension\Dependency\Plugin\PermissionStoragePluginInterface $permissionStoragePlugin)
 *
 * @SuppressWarnings(PHPMD)
 */
class OrderExperienceManagementBusinessTester extends Actor
{
    use _generated\OrderExperienceManagementBusinessTesterActions;

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface> $scheduleValidatorPlugins
     */
    public function setScheduleValidatorPlugins(array $scheduleValidatorPlugins): void
    {
        $this->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_SCHEDULE_VALIDATOR, $scheduleValidatorPlugins);
    }

    public function disableScheduleValidatorPlugins(): void
    {
        $this->setScheduleValidatorPlugins([]);
    }

    public function createFixedScheduleValidatorPlugin(
        string $sku,
        bool $isPurchasable,
        string $reviewReason,
        ?int $currentPrice = null,
    ): ScheduleValidatorPluginInterface {
        return new FixedScheduleValidatorPlugin($sku, $isPurchasable, $reviewReason, $currentPrice);
    }

    /**
     * @param array<string, array<int, int>> $priceMapByKey Current catalog [unitGrossPrice, unitNetPrice] keyed by group key.
     */
    public function createPriceScheduleValidatorPlugin(array $priceMapByKey): ScheduleValidatorPluginInterface
    {
        $priceCartConnectorFacade = Stub::makeEmpty(PriceCartConnectorFacadeInterface::class, [
            'addPriceToItems' => static function (CartChangeTransfer $cartChangeTransfer) use ($priceMapByKey): CartChangeTransfer {
                foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
                    $key = $itemTransfer->getGroupKey() ?? $itemTransfer->getBundleItemIdentifier() ?? $itemTransfer->getSku();

                    if (!isset($priceMapByKey[$key])) {
                        continue;
                    }

                    [$unitGrossPrice, $unitNetPrice] = $priceMapByKey[$key];
                    $itemTransfer->setUnitGrossPrice($unitGrossPrice)->setUnitNetPrice($unitNetPrice);
                }

                return $cartChangeTransfer;
            },
        ]);
        $productPackagingUnitFacade = Stub::makeEmpty(ProductPackagingUnitFacadeInterface::class, [
            'setCustomAmountPrice' => static fn (CartChangeTransfer $cartChangeTransfer): CartChangeTransfer => $cartChangeTransfer,
        ]);

        $businessFactory = new class ($priceCartConnectorFacade, $productPackagingUnitFacade) extends OrderExperienceManagementBusinessFactory {
            public function __construct(
                protected PriceCartConnectorFacadeInterface $priceCartConnectorFacade,
                protected ProductPackagingUnitFacadeInterface $productPackagingUnitFacade,
            ) {
            }

            public function getPriceCartConnectorFacade(): PriceCartConnectorFacadeInterface
            {
                return $this->priceCartConnectorFacade;
            }

            public function getProductPackagingUnitFacade(): ProductPackagingUnitFacadeInterface
            {
                return $this->productPackagingUnitFacade;
            }
        };

        $priceScheduleValidatorPlugin = new PriceScheduleValidatorPlugin();
        $priceScheduleValidatorPlugin->setBusinessFactory($businessFactory);

        return $priceScheduleValidatorPlugin;
    }

    public function pinMailFacadeDependency(): void
    {
        $this->mockFactoryMethod('getMailFacade', Stub::makeEmpty(MailFacadeInterface::class));
    }

    public function enableStateMachineConfirmation(int $idRecurringSchedule): void
    {
        $this->seedStateMachineReviewState($idRecurringSchedule);
        $this->stubStateMachineFacadeTriggerEvent(1);
    }

    /**
     * Seeds the review state but makes the confirm event affect no transition, so approval reaches the
     * confirm step and fails there — without cascading into the real place-order state machine.
     */
    public function failStateMachineConfirmation(int $idRecurringSchedule): void
    {
        $this->seedStateMachineReviewState($idRecurringSchedule);
        $this->stubStateMachineFacadeTriggerEvent(0);
    }

    protected function seedStateMachineReviewState(int $idRecurringSchedule): void
    {
        $stateMachineProcessEntity = SpyStateMachineProcessQuery::create()
            ->filterByStateMachineName(OrderExperienceManagementConfig::STATE_MACHINE_NAME)
            ->filterByName(OrderExperienceManagementConfig::PROCESS_NAME)
            ->findOneOrCreate();
        $stateMachineProcessEntity->save();

        $stateMachineItemStateEntity = SpyStateMachineItemStateQuery::create()
            ->filterByFkStateMachineProcess($stateMachineProcessEntity->getIdStateMachineProcess())
            ->filterByName(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED)
            ->findOneOrCreate();
        $stateMachineItemStateEntity->save();

        (new SpyStateMachineItemStateHistory())
            ->setFkStateMachineItemState($stateMachineItemStateEntity->getIdStateMachineItemState())
            ->setIdentifier($idRecurringSchedule)
            ->save();
    }

    protected function stubStateMachineFacadeTriggerEvent(int $affectedTransitionCount): void
    {
        $this->setDependency(
            OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE,
            Stub::makeEmpty(StateMachineFacadeInterface::class, ['triggerEvent' => $affectedTransitionCount]),
        );
    }
}
