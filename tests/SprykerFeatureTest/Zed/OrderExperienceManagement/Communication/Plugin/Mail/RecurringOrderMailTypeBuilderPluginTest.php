<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\Mail;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MailTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\AbstractRecurringOrderMailTypeBuilderPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderFailureMailTypeBuilderPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderUpcomingNotificationMailTypeBuilderPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderValidationFailedMailTypeBuilderPlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group Mail
 * @group RecurringOrderMailTypeBuilderPluginTest
 * Add your own group annotations below this line
 */
class RecurringOrderMailTypeBuilderPluginTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testFailurePluginExposesMailTypeAndBuildsTemplates(): void
    {
        $this->assertMailTypeBuilderPlugin(
            new RecurringOrderFailureMailTypeBuilderPlugin(),
            RecurringOrderFailureMailTypeBuilderPlugin::MAIL_TYPE,
        );
    }

    public function testUpcomingNotificationPluginExposesMailTypeAndBuildsTemplates(): void
    {
        $this->assertMailTypeBuilderPlugin(
            new RecurringOrderUpcomingNotificationMailTypeBuilderPlugin(),
            RecurringOrderUpcomingNotificationMailTypeBuilderPlugin::MAIL_TYPE,
        );
    }

    public function testValidationFailedPluginExposesMailTypeAndBuildsTemplates(): void
    {
        $this->assertMailTypeBuilderPlugin(
            new RecurringOrderValidationFailedMailTypeBuilderPlugin(),
            RecurringOrderValidationFailedMailTypeBuilderPlugin::MAIL_TYPE,
        );
    }

    protected function assertMailTypeBuilderPlugin(
        AbstractRecurringOrderMailTypeBuilderPlugin $mailTypeBuilderPlugin,
        string $expectedMailType,
    ): void {
        $this->assertSame($expectedMailType, $mailTypeBuilderPlugin->getName());

        $mailTransfer = $mailTypeBuilderPlugin->build(new MailTransfer());
        $mailTemplateTransfers = $mailTransfer->getTemplates();

        $this->assertCount(2, $mailTemplateTransfers);

        $htmlTemplateTransfer = $mailTemplateTransfers->offsetGet(0);
        $this->assertTrue($htmlTemplateTransfer->getIsHtml());
        $this->assertStringEndsWith('.html.twig', (string)$htmlTemplateTransfer->getName());

        $textTemplateTransfer = $mailTemplateTransfers->offsetGet(1);
        $this->assertFalse($textTemplateTransfer->getIsHtml());
        $this->assertStringEndsWith('.text.twig', (string)$textTemplateTransfer->getName());
    }
}
