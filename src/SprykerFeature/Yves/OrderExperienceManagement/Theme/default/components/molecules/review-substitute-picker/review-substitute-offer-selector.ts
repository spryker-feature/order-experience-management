import { Events as AutocompleteEvents } from 'ShopUi/components/molecules/autocomplete-form/autocomplete-form';

const OFFER_REFERENCE_FIELD = 'product_offer_reference';

export interface OfferSelectorSelectors {
    option: string;
    optionPrice: string;
    offerContainer: string;
    autocompleteHook: string;
}

/**
 * The merchant offer select rendered by MerchantProductOffersSelectWidget. Offers are requested through the
 * autocomplete hook the widget listens on, and the widget — being a single form — is moved under whichever option
 * row is selected. Restoring a stored reference has to wait for the widget to answer, hence the observer.
 */
export default class ReviewSubstituteOfferSelector {
    constructor(
        protected readonly picker: HTMLElement,
        protected readonly selectors: OfferSelectorSelectors,
        protected readonly onOfferApplied: () => void,
    ) {}

    reload(sku: string): void {
        this.picker
            .querySelector<HTMLElement>(this.selectors.autocompleteHook)
            ?.dispatchEvent(new CustomEvent(AutocompleteEvents.SET, { detail: { value: sku } }));
    }

    relocateUnderSelected(radio: HTMLInputElement): void {
        const optionPrice = radio.closest(this.selectors.option)?.querySelector(this.selectors.optionPrice);
        const container = this.container;

        if (optionPrice && container) {
            optionPrice.after(container);
        }
    }

    preselect(sku: string, productOfferReference: string): void {
        this.reload(sku);

        const container = this.container;

        if (this.apply(productOfferReference) || !container) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (this.apply(productOfferReference)) {
                observer.disconnect();
            }
        });

        observer.observe(container, { childList: true, subtree: true });
    }

    get selectedProductOfferReference(): string {
        return this.select?.value ?? '';
    }

    get selectedMerchantLabel(): string {
        return this.select?.selectedOptions?.[0]?.textContent?.trim() ?? '';
    }

    protected apply(productOfferReference: string): boolean {
        const select = this.select;
        const hasOption = Array.from(select?.options ?? []).some(
            (option: HTMLOptionElement) => option.value === productOfferReference,
        );

        if (!select || !hasOption) {
            return false;
        }

        select.value = productOfferReference;
        this.onOfferApplied();

        return true;
    }

    protected get container(): HTMLElement | null {
        return this.picker.querySelector<HTMLElement>(this.selectors.offerContainer);
    }

    protected get select(): HTMLSelectElement | null {
        return this.picker.querySelector<HTMLSelectElement>(`select[name="${OFFER_REFERENCE_FIELD}"]`);
    }
}
