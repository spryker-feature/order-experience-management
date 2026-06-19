<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class RecurringOrderResumeForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderResumeForm';

    public const string FIELD_NEXT_EXECUTION_DATE = 'nextExecutionDate';

    public const string FIELD_UUID = 'uuid';

    protected const string COMPARISON_VALUE_TODAY = 'today';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this
            ->addUuidField($builder)
            ->addNextExecutionDateField($builder);
    }

    protected function addUuidField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_UUID, HiddenType::class);

        return $this;
    }

    protected function addNextExecutionDateField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_NEXT_EXECUTION_DATE, DateType::class, [
            'widget' => 'single_text',
            'input' => 'datetime',
            'required' => true,
            'label' => 'recurring_orders.detail.resume.date_label',
            'constraints' => [
                new NotBlank(['message' => 'recurring_orders.detail.resume.validation.date_required']),
                new GreaterThanOrEqual([
                    'value' => static::COMPARISON_VALUE_TODAY,
                    'message' => 'recurring_orders.detail.resume.validation.date_in_past',
                ]),
            ],
        ]);

        return $this;
    }
}
