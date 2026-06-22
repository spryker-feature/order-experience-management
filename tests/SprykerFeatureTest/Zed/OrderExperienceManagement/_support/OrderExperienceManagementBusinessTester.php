<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement;

use Codeception\Actor;
use Codeception\Stub;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
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

    public function pinMailFacadeDependency(): void
    {
        $this->mockFactoryMethod('getMailFacade', Stub::makeEmpty(MailFacadeInterface::class));
    }

    public function enableStateMachineConfirmation(int $idRecurringSchedule): void
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

        $this->setDependency(
            OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE,
            Stub::makeEmpty(StateMachineFacadeInterface::class, ['triggerEvent' => 1]),
        );
    }
}
