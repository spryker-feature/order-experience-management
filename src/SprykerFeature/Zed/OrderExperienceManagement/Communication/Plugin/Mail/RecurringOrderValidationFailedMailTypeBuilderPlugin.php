<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail;

class RecurringOrderValidationFailedMailTypeBuilderPlugin extends AbstractRecurringOrderMailTypeBuilderPlugin
{
    public const string MAIL_TYPE = 'recurring_orders.notify_buyer_validation_failed';

    protected const string MAIL_TEMPLATE_HTML = 'OrderExperienceManagement/Mail/notify-buyer-validation-failed.html.twig';

    protected const string MAIL_TEMPLATE_TEXT = 'OrderExperienceManagement/Mail/notify-buyer-validation-failed.text.twig';

    protected function getMailType(): string
    {
        return static::MAIL_TYPE;
    }

    protected function getHtmlTemplateName(): string
    {
        return static::MAIL_TEMPLATE_HTML;
    }

    protected function getTextTemplateName(): string
    {
        return static::MAIL_TEMPLATE_TEXT;
    }
}
