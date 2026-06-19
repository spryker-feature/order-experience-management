<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderSearchForm extends AbstractType
{
    public const string FORM_NAME = 'recurring_order_search_form';

    public const string FIELD_FILTERS = 'filters';

    public const string FIELD_ORDER_BY = 'orderBy';

    public const string FIELD_ORDER_DIRECTION = 'orderDirection';

    public const string FIELD_RESET = 'reset';

    public const string OPTION_SCOPE_CHOICES = 'scopeChoices';

    public const string OPTION_STATUS_CHOICES = 'statusChoices';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            static::OPTION_SCOPE_CHOICES,
            static::OPTION_STATUS_CHOICES,
        ]);

        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addOrderByField($builder)
            ->addOrderDirectionField($builder)
            ->addResetField($builder)
            ->addFiltersSubForm($builder, $options);
    }

    protected function addResetField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_RESET, HiddenType::class, [
            'required' => false,
            'label' => false,
        ]);

        return $this;
    }

    protected function addOrderByField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ORDER_BY, HiddenType::class, [
            'required' => false,
        ]);

        return $this;
    }

    protected function addOrderDirectionField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ORDER_DIRECTION, HiddenType::class, [
            'required' => false,
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addFiltersSubForm(FormBuilderInterface $builder, array $options): static
    {
        $builder->add(static::FIELD_FILTERS, RecurringOrderSearchFilterSubForm::class, [
            RecurringOrderSearchFilterSubForm::OPTION_SCOPE_CHOICES => $options[static::OPTION_SCOPE_CHOICES],
            RecurringOrderSearchFilterSubForm::OPTION_STATUS_CHOICES => $options[static::OPTION_STATUS_CHOICES],
        ]);

        return $this;
    }
}
