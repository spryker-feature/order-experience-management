import Component from 'ShopUi/models/component';
import MainPopup, {
    EVENT_CLOSE_POPUP,
    EVENT_POPUP_CONTENT_MOUNTED,
} from 'ShopUi/components/molecules/main-popup/main-popup';
import { EVENT_FORMATTED_NUMBER } from 'ShopUi/components/molecules/formatted-number-input/formatted-number-input';
import ReviewAddedItems, {
    AddedItemEntryValues,
    EVENT_ADDED_ITEM_REMOVE,
    EVENT_ADDED_ITEM_UPDATE,
    EVENT_ADDED_ITEM_WRITE,
} from '../review-added-items/review-added-items';
import { LINE_ITEMS_UPDATE_EVENT } from '../review-summary-banner-price/review-sumary-banner-price';
import ReviewSubstitutePicker, {
    EVENT_REVIEW_SUBSTITUTE_CONFIRMED,
    EVENT_REVIEW_SUBSTITUTE_PRICE_UPDATED,
    SubstitutePriceDetail,
    SubstituteSelectionDetail,
} from '../review-substitute-picker/review-substitute-picker';
import ReviewSubstituteQuantityInput from './review-substitute-quantity-input';
import ReviewSubstituteSummaryRenderer from './review-substitute-summary-renderer';

const EMPTY_SELECTION = { entryKey: '', sku: '', productName: '', merchantLabel: '', unitPrice: 0 };

/**
 * Summary side of the substitute flow; the option list lives in review-substitute-picker, and the `addedItems` form
 * entry is the state both halves read back. main-popup moves the picker into a <body>-level clone, so it is reached
 * by listening for its bubbled events on document — filtered by entry key, as one page holds an instance per
 * flagged item — and by calling back into it once that clone is mounted.
 */
export default class ReviewSubstitute extends Component {
    protected addedItems: ReviewAddedItems | null;

    protected modal: MainPopup | null;

    protected removeButton: HTMLButtonElement | null;

    protected quantityInput: HTMLInputElement | null;

    protected quantityControl: ReviewSubstituteQuantityInput;

    protected summaryRenderer: ReviewSubstituteSummaryRenderer;

    protected applied: SubstitutePriceDetail = EMPTY_SELECTION;

    protected readyCallback(): void {}

    protected init(): void {
        this.addedItems = document.querySelector<ReviewAddedItems>('review-added-items');
        this.modal = this.querySelector<MainPopup>(`.${this.jsName}__product-popup`);
        this.removeButton = this.querySelector(`.${this.jsName}__remove`);
        this.quantityInput = this.querySelector(`.${this.jsName}__qty`);
        this.quantityControl = new ReviewSubstituteQuantityInput(this.quantityInput);
        this.summaryRenderer = new ReviewSubstituteSummaryRenderer(this);

        this.mapEvents();
        this.restoreFromEntry();
    }

    protected mapEvents(): void {
        this.removeButton?.addEventListener('click', () => this.removeSubstitute());
        this.quantityInput?.addEventListener('input', () => this.onQuantityChange());

        this.quantityInput?.addEventListener(EVENT_FORMATTED_NUMBER, () => {
            this.quantityControl.correct(this.getSelectedQuantity());
            this.onQuantityChange();
        });

        this.addEventListener(
            'keydown',
            (event: KeyboardEvent) => this.quantityControl.preventDecrementBelowMinimum(event),
            true,
        );
        this.modal?.addEventListener(EVENT_POPUP_CONTENT_MOUNTED, () => this.onPopupContentMounted());
        document.addEventListener(EVENT_REVIEW_SUBSTITUTE_CONFIRMED, (event: Event) =>
            this.onSubstituteConfirmed(event as CustomEvent<SubstituteSelectionDetail>),
        );
        document.addEventListener(EVENT_REVIEW_SUBSTITUTE_PRICE_UPDATED, (event: Event) =>
            this.onSubstitutePriceUpdated(event as CustomEvent<SubstitutePriceDetail>),
        );
    }

    protected onPopupContentMounted(): void {
        if (this.hasEntry) {
            this.restorePicker();

            return;
        }

        this.picker?.selectDefault();
    }

    protected onSubstituteConfirmed(event: CustomEvent<SubstituteSelectionDetail>): void {
        if (event.detail.entryKey !== this.entryKey) {
            return;
        }

        this.applied = event.detail;
        this.applySubstitute(event.detail);
        this.summaryRenderer.showAppliedState();
        this.closeModal();
    }

    /**
     * The offer-specific price is resolved asynchronously while the modal is open. It only reaches the summary once
     * the substitute is applied; before that the picker is the only one showing it.
     */
    protected onSubstitutePriceUpdated(event: CustomEvent<SubstitutePriceDetail>): void {
        if (event.detail.entryKey !== this.entryKey || !this.hasEntry) {
            return;
        }

        this.applied = event.detail;
        this.requestEntryUpdate({ productName: this.applied.productName, unitPrice: this.applied.unitPrice });
        this.renderSummary(this.getSelectedQuantity());

        this.triggerRecalculation();
    }

    protected applySubstitute(detail: SubstituteSelectionDetail): void {
        if (this.applied.sku === '') {
            return;
        }

        const quantity = this.getSelectedQuantity();
        this.requestEntryWrite({
            sku: this.applied.sku,
            quantity,
            productOfferReference: detail.productOfferReference,
            shippingAddressKey: detail.shippingAddressKey,
            idShippingAddress: detail.idShippingAddress,
            idShipmentMethod: detail.idShipmentMethod,
            productName: this.applied.productName,
            unitPrice: this.applied.unitPrice,
        });
        this.renderSummary(quantity);

        this.triggerRecalculation();
    }

    protected onQuantityChange(): void {
        if (!this.hasEntry) {
            return;
        }

        const quantity = this.getSelectedQuantity();
        this.requestEntryUpdate({ quantity });
        this.renderSummary(quantity);

        this.triggerRecalculation();
    }

    protected requestEntryWrite(values: AddedItemEntryValues): void {
        this.dispatchCustomEvent(EVENT_ADDED_ITEM_WRITE, { entryKey: this.entryKey, values }, { bubbles: true });
    }

    protected requestEntryUpdate(values: Partial<AddedItemEntryValues>): void {
        this.dispatchCustomEvent(EVENT_ADDED_ITEM_UPDATE, { entryKey: this.entryKey, values }, { bubbles: true });
    }

    protected getSelectedQuantity(): number {
        return this.quantityControl.read(Number(this.getAttribute('quantity')));
    }

    protected renderSummary(quantity: number): void {
        this.summaryRenderer.render({ ...this.applied, quantity });
    }

    protected removeSubstitute(): void {
        this.dispatchCustomEvent(EVENT_ADDED_ITEM_REMOVE, { entryKey: this.entryKey }, { bubbles: true });
        this.applied = { ...this.applied, merchantLabel: '' };
        this.summaryRenderer.clear();
        this.summaryRenderer.showChooseState();

        this.triggerRecalculation();
    }

    protected restoreFromEntry(): void {
        const entry = this.addedItems?.readEntry(this.entryKey);

        if (!entry) {
            return;
        }

        // Falls back to the quantity the field was rendered with, as the input still holds it at this point.
        const quantity = entry.quantity || this.getSelectedQuantity();
        this.applied = {
            entryKey: this.entryKey,
            sku: entry.sku,
            productName: entry.productName || entry.sku,
            merchantLabel: '',
            unitPrice: entry.unitPrice,
        };

        this.quantityControl.write(quantity);
        this.renderSummary(quantity);
        this.summaryRenderer.showAppliedState();

        // The picker fills in the merchant of the stored offer, the one summary value the form entry cannot carry,
        // before the modal is opened for the first time. Deferred because ShopUi mounts all components in a single
        // synchronous pass in registry order, so the picker may not have run its own init() yet at this point.
        requestAnimationFrame(() => {
            this.restorePicker();
            this.triggerRecalculation();
        });
    }

    protected restorePicker(): void {
        const entry = this.addedItems?.readEntry(this.entryKey);

        if (entry) {
            this.picker?.restore(entry);
        }
    }

    protected closeModal(): void {
        this.modal?.dispatchEvent(new CustomEvent(EVENT_CLOSE_POPUP));
    }

    /** The summary line owns the totals the banner reads, so the collection itself does not announce the change. */
    protected triggerRecalculation(): void {
        this.dispatchCustomEvent(LINE_ITEMS_UPDATE_EVENT, {}, { bubbles: true });
    }

    protected get hasEntry(): boolean {
        return this.addedItems?.hasEntry(this.entryKey) === true;
    }

    /** Resolves the picker both before and after main-popup has moved the modal content into its body-level clone. */
    protected get picker(): ReviewSubstitutePicker | null {
        const popupContainer = document.getElementById(this.modal?.getAttribute('content-id') ?? '') ?? this.modal;

        return popupContainer?.querySelector<ReviewSubstitutePicker>('review-substitute-picker') ?? null;
    }

    protected get entryKey(): string {
        return this.getAttribute('entry-key') ?? '';
    }
}
