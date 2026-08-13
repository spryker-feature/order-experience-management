import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';
import ReviewShipmentSelection from '../review-shipment-selection/review-shipment-selection';
import ReviewSubstituteOfferSelector from './review-substitute-offer-selector';

const CENTS_IN_UNIT = 100;

const PRICE_DECIMAL_PLACES = 2;

export const EVENT_REVIEW_SUBSTITUTE_CONFIRMED = 'reviewSubstituteConfirmed';

export const EVENT_REVIEW_SUBSTITUTE_PRICE_UPDATED = 'reviewSubstitutePriceUpdated';

export interface SubstitutePriceDetail {
    entryKey: string;
    sku: string;
    productName: string;
    merchantLabel: string;
    unitPrice: number;
}

export interface SubstituteSelectionDetail extends SubstitutePriceDetail {
    productOfferReference: string;
    shippingAddressKey: string;
    idShippingAddress: string;
    idShipmentMethod: string;
}

export interface RestoredSubstitute {
    sku: string;
    productOfferReference: string;
    shippingAddressKey: string;
    idShipmentMethod: string;
}

/**
 * Modal side of the substitute flow. main-popup moves this markup into a <body>-level clone on first open, so the
 * element is initialized once in place and once in the clone: init() only wires listeners to its own subtree, and
 * the host kicks off every request through selectDefault()/restore() once the clone is mounted, which leaves the
 * discarded instance idle. Events reach the host by bubbling to document, as the clone is not a descendant of it.
 */
export default class ReviewSubstitutePicker extends Component {
    protected shipmentSelection: ReviewShipmentSelection | null;

    protected priceProvider: AjaxProvider | null;

    protected offerSelector: ReviewSubstituteOfferSelector;

    protected selectedRadio: HTMLInputElement | null = null;

    protected selectedSku = '';

    protected selectedName = '';

    protected selectedBasePrice = 0;

    protected selectedPrice = 0;

    protected init(): void {
        this.shipmentSelection = this.querySelector<ReviewShipmentSelection>('review-shipment-selection');
        this.priceProvider = this.querySelector<AjaxProvider>(`.${this.getAttribute('price-provider-class') ?? ''}`);
        this.offerSelector = new ReviewSubstituteOfferSelector(
            this,
            {
                option: `.${this.jsName}__option`,
                optionPrice: `.${this.jsName}__option-price`,
                offerContainer: `.${this.jsName}__offer`,
                autocompleteHook: `.${this.getAttribute('autocomplete-class') ?? ''}`,
            },
            () => this.onOfferApplied(),
        );

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.radios.forEach((radio: HTMLInputElement) =>
            radio.addEventListener('change', () => this.onRadioChange(radio)),
        );
        this.addEventListener('change', (event: Event) => this.onSelectionChange(event));
        this.querySelector<HTMLButtonElement>(`.${this.getAttribute('confirm-class') ?? ''}`)?.addEventListener(
            'click',
            () => this.confirmSelection(),
        );
    }

    selectDefault(): void {
        const defaultRadio = this.radios[0];

        if (!defaultRadio) {
            return;
        }

        this.captureRadio(defaultRadio);
        this.offerSelector.relocateUnderSelected(defaultRadio);
        this.offerSelector.reload(defaultRadio.value);
        this.refreshShipmentMethods();
    }

    restore(values: RestoredSubstitute): void {
        const radio = this.radios.find((candidate: HTMLInputElement) => candidate.value === values.sku) ?? null;

        if (radio) {
            radio.checked = true;
            this.captureRadio(radio);
            this.offerSelector.relocateUnderSelected(radio);
        }

        if (!radio) {
            this.clearRadioSelection();
            this.selectedSku = values.sku;
            this.selectedName = values.sku;
        }

        if (values.productOfferReference !== '') {
            this.offerSelector.preselect(values.sku, values.productOfferReference);
        }

        if (values.shippingAddressKey !== '') {
            this.restoreShipment(values.shippingAddressKey, values.idShipmentMethod);
        }
    }

    protected onRadioChange(radio: HTMLInputElement): void {
        this.captureRadio(radio);
        this.offerSelector.relocateUnderSelected(radio);
        this.offerSelector.reload(radio.value);
        this.refreshShipmentMethods();
        this.refreshPrice();
    }

    /** The offer select is the widget's own field, so it is recognized by the container it was moved into. */
    protected onSelectionChange(event: Event): void {
        if ((event.target as HTMLElement | null)?.closest(`.${this.jsName}__offer`)) {
            this.onOfferApplied();
        }
    }

    protected formatPrice(amountInCents: number): string {
        const amount = amountInCents / CENTS_IN_UNIT;
        const currency = this.getAttribute('currency') ?? '';

        return currency === ''
            ? amount.toFixed(PRICE_DECIMAL_PLACES)
            : amount.toLocaleString(document.documentElement.lang || 'en', { style: 'currency', currency });
    }

    protected onOfferApplied(): void {
        this.refreshShipmentMethods();
        this.refreshPrice();
    }

    protected confirmSelection(): void {
        const shippingAddressKey = this.shipmentSelection?.selectedShippingAddress ?? '';
        const idShipmentMethod = this.shipmentSelection?.selectedShipmentMethod ?? '';

        if (shippingAddressKey === '' || idShipmentMethod === '') {
            return;
        }

        this.dispatchCustomEvent(
            EVENT_REVIEW_SUBSTITUTE_CONFIRMED,
            {
                ...this.priceDetail,
                productOfferReference: this.offerSelector.selectedProductOfferReference,
                shippingAddressKey,
                idShippingAddress: this.shipmentSelection?.selectedIdCompanyUnitAddress ?? '',
                idShipmentMethod,
            },
            { bubbles: true },
        );
    }

    protected refreshShipmentMethods(): void {
        this.shipmentSelection?.refreshMethods(this.productContext);
    }

    protected restoreShipment(shippingAddressKey: string, idShipmentMethod: string): void {
        this.shipmentSelection?.restore(this.productContext, shippingAddressKey, idShipmentMethod);
    }

    protected async refreshPrice(): Promise<void> {
        const productOfferReference = this.offerSelector.selectedProductOfferReference;

        if (!this.priceProvider || this.selectedSku === '' || productOfferReference === '') {
            this.applyResolvedPrice(this.selectedBasePrice);

            return;
        }

        // Cleared first, as the same provider is reused across requests.
        this.priceProvider.queryParams.clear();
        this.priceProvider.queryParams.set('sku', this.selectedSku);
        this.priceProvider.queryParams.set('productOfferReference', productOfferReference);

        const price = (await this.priceProvider.fetch<string>().catch(() => '')).trim();
        this.applyResolvedPrice(price === '' ? this.selectedBasePrice : Number(price));
    }

    protected applyResolvedPrice(price: number): void {
        this.selectedPrice = price;
        this.renderOptionPrice(this.selectedRadio, this.selectedPrice);
        this.dispatchCustomEvent(EVENT_REVIEW_SUBSTITUTE_PRICE_UPDATED, this.priceDetail, { bubbles: true });
    }

    protected renderOptionPrice(radio: HTMLInputElement | null, amountInCents: number): void {
        const priceElement = radio?.closest(`.${this.jsName}__option`)?.querySelector(`.${this.jsName}__option-price`);

        if (priceElement) {
            priceElement.textContent = this.formatPrice(amountInCents);
        }
    }

    protected captureRadio(radio: HTMLInputElement): void {
        if (this.selectedRadio && this.selectedRadio !== radio) {
            this.renderOptionPrice(this.selectedRadio, Number(this.selectedRadio.getAttribute('data-price')) || 0);
        }

        this.selectedRadio = radio;
        this.selectedSku = radio.value;
        this.selectedName = radio.getAttribute('data-name') ?? radio.value;
        this.selectedBasePrice = Number(radio.getAttribute('data-price')) || 0;
        this.selectedPrice = this.selectedBasePrice;
    }

    protected clearRadioSelection(): void {
        if (this.selectedRadio) {
            this.renderOptionPrice(this.selectedRadio, Number(this.selectedRadio.getAttribute('data-price')) || 0);
        }

        this.radios.forEach((radio: HTMLInputElement) => {
            radio.checked = false;
        });

        this.selectedRadio = null;
    }

    protected get priceDetail(): SubstitutePriceDetail {
        return {
            entryKey: this.getAttribute('entry-key') ?? '',
            sku: this.selectedSku,
            productName: this.selectedName,
            merchantLabel: this.offerSelector.selectedMerchantLabel,
            unitPrice: this.selectedPrice,
        };
    }

    protected get productContext(): { sku: string; productOfferReference: string } {
        return { sku: this.selectedSku, productOfferReference: this.offerSelector.selectedProductOfferReference };
    }

    protected get radios(): HTMLInputElement[] {
        return Array.from(this.querySelectorAll<HTMLInputElement>(`.${this.jsName}__radio-option`));
    }
}
