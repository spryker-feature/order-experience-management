<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail;

use Generated\Shared\Transfer\MailTemplateTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MailExtension\Dependency\Plugin\MailTypeBuilderPluginInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
abstract class AbstractRecurringOrderMailTypeBuilderPlugin extends AbstractPlugin implements MailTypeBuilderPluginInterface
{
    abstract protected function getMailType(): string;

    abstract protected function getHtmlTemplateName(): string;

    abstract protected function getTextTemplateName(): string;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return $this->getMailType();
    }

    /**
     * {@inheritDoc}
     *
     * - Requires `MailTransfer.customer` to be set.
     * - Requires `MailTransfer.customer.email` to be set.
     * - Requires `MailTransfer.recurringSchedule` to be set.
     * - Builds the `MailTransfer` with the recurring-order notification HTML and text templates.
     *
     * @api
     */
    public function build(MailTransfer $mailTransfer): MailTransfer
    {
        return $mailTransfer
            ->addTemplate(
                (new MailTemplateTransfer())
                    ->setName($this->getHtmlTemplateName())
                    ->setIsHtml(true),
            )
            ->addTemplate(
                (new MailTemplateTransfer())
                    ->setName($this->getTextTemplateName())
                    ->setIsHtml(false),
            );
    }
}
