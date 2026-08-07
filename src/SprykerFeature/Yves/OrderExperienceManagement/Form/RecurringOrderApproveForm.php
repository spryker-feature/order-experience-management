<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * CSRF-protected form for the Review Required approval. Carries the schedule UUID and the prices the
 * buyer accepted on the page (each entry holding its group key + price), so approval is validated and
 * anchored to what was shown.
 *
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderApproveForm extends AbstractRecurringOrderUuidForm
{
    public const string FORM_NAME = 'recurringOrderApproveForm';

    public const string FIELD_ACCEPTED_ITEMS = 'acceptedItems';

    public const string FIELD_ADDED_ITEMS = 'addedItems';

    public const string FIELD_SCOPE = 'scope';

    public const string OPTION_RECURRING_SCHEDULE_REVIEW = 'recurringScheduleReview';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScopeChosenApprovalValidator::GLOSSARY_KEY_SCOPE_REQUIRED
     */
    protected const string GLOSSARY_KEY_SCOPE_REQUIRED = 'recurring_orders.review.scope_required';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            static::OPTION_RECURRING_SCHEDULE_REVIEW => null,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUuidField($builder, true);
        $this->addAcceptedItemsField($builder);
        $this->addAddedItemsField($builder);
        $this->addScopeField($builder);
        $this->addFormExpanderFields($builder, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addFormExpanderFields(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getFactory()->getRecurringOrderApproveFormExpanderPlugins() as $recurringOrderApproveFormExpanderPlugin) {
            $recurringOrderApproveFormExpanderPlugin->expandForm($builder, $options);
        }
    }

    protected function addScopeField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_SCOPE, HiddenType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'data-scope' => true,
                'data-scope-choices' => json_encode($this->getScopeChoices()),
            ],
            'constraints' => [
                new Callback([$this, 'validateScopeIsOffered']),
            ],
        ]);

        return $this;
    }

    public function validateScopeIsOffered(?string $scope, ExecutionContextInterface $context): void
    {
        if ($scope === null || $scope === '') {
            return;
        }

        if (in_array($scope, $this->getConfig()->getReviewScopeChoices(), true)) {
            return;
        }

        $context->buildViolation(static::GLOSSARY_KEY_SCOPE_REQUIRED)
            ->addViolation();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function getScopeChoices(): array
    {
        $choices = [];
        foreach ($this->getConfig()->getReviewScopeChoices() as $glossaryKey => $scope) {
            $choices[] = [
                'value' => $scope,
                'label' => $glossaryKey,
            ];
        }

        return $choices;
    }

    protected function addAcceptedItemsField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ACCEPTED_ITEMS, CollectionType::class, [
            'required' => false,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => RecurringOrderAcceptedItemForm::class,
            'entry_options' => [
                'label' => false,
            ],
        ]);

        return $this;
    }

    protected function addAddedItemsField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ADDED_ITEMS, CollectionType::class, [
            'required' => false,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => RecurringOrderAddedItemForm::class,
            'entry_options' => [
                'label' => false,
            ],
        ]);

        return $this;
    }
}
