/* eslint-disable max-lines */
import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';
import ReviewShipmentSelection from '../review-shipment-selection/review-shipment-selection';

const CENTS_IN_UNIT = 100;

const PRICE_DECIMAL_PLACES = 2;

export const EVENT_REVIEW_PRODUCT_PICKED = 'reviewProductPicked';

export interface PickedProductDetail {
    sku: string;
    quantity: number;
    productOfferReference: string;
    shippingAddressKey: string;
    idShippingAddress: string;
    idShipmentMethod: string;
    productName: string;
    unitPrice: number;
}

export default class ReviewAddProductPicker extends Component {
    protected static readonly OFFER_REFERENCE_FIELD = 'product_offer_reference';

    protected static readonly AUTOCOMPLETE_TEXT_INPUT_CLASS = 'js-autocomplete-form__text-input';

    protected skuInput: HTMLInputElement | null;

    protected quantityInput: HTMLInputElement | null;

    protected addButton: HTMLButtonElement | null;

    protected shipmentSelection: ReviewShipmentSelection | null;

    protected priceProvider: AjaxProvider | null;

    protected priceContainer: HTMLElement | null;

    protected selectedSku = '';

    protected selectedName = '';

    protected selectedBasePrice = 0;

    protected selectedPrice = 0;

    protected init(): void {
        this.skuInput = this.querySelector(`input[name="${this.getAttribute('sku-field-name') ?? ''}"]`);
        this.quantityInput = this.querySelector(`.${this.jsName}__quantity`);
        this.addButton = this.querySelector(`.${this.jsName}__add`);
        this.shipmentSelection = this.querySelector<ReviewShipmentSelection>('review-shipment-selection');
        this.priceProvider = this.querySelector<AjaxProvider>(`.${this.jsName}__price-provider`);
        this.priceContainer = this.querySelector(`.${this.jsName}__price`);

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.addButton?.addEventListener('click', () => this.onAdd());
        this.querySelector(`.${this.jsName}__clear`)?.addEventListener('click', () => this.reset());
        this.quantityInput?.addEventListener('input', () => this.renderPricePreview());
        this.addEventListener('input', (event: Event) => this.onSearchInput(event));

        this.quantityInput?.addEventListener('change', () => this.correctQuantityInputValue());
        this.addEventListener('click', (event: Event) => this.captureSelection(event), true);
        this.addEventListener('change', (event: Event) => this.onSelectionChange(event));
    }

    protected captureSelection(event: Event): void {
        const itemElement = (event.target as HTMLElement | null)?.closest<HTMLElement>('.products-list__item');

        if (!itemElement) {
            return;
        }

        this.selectedSku = itemElement.getAttribute('data-value') ?? '';
        this.selectedName = itemElement.getAttribute('data-name') ?? this.selectedSku;
        this.selectedBasePrice = Number(itemElement.getAttribute('data-price')) || 0;
        this.selectedPrice = this.selectedBasePrice;

        this.renderPricePreview();
        this.refreshShipmentMethods();
        this.refreshPrice();
    }

    protected onSearchInput(event: Event): void {
        const input = event.target as HTMLInputElement | null;

        if (!input?.classList.contains(ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS)) {
            return;
        }

        if (input.value.trim() === '' && this.selectedSku !== '') {
            this.clearSelection();
        }
    }

    protected clearSelection(): void {
        this.selectedSku = '';
        this.selectedName = '';
        this.selectedBasePrice = 0;
        this.selectedPrice = 0;
        this.setInputValue(this.skuInput, '');
        this.renderPricePreview();
    }

    protected onSelectionChange(event: Event): void {
        const targetName = (event.target as HTMLElement | null)?.getAttribute('name');

        if (targetName === ReviewAddProductPicker.OFFER_REFERENCE_FIELD) {
            this.refreshShipmentMethods();
            this.refreshPrice();
        }
    }

    protected refreshShipmentMethods(): void {
        this.shipmentSelection?.refreshMethods({
            sku: this.selectedSku,
            productOfferReference: this.selectedProductOfferReference,
        });
    }

    protected async refreshPrice(): Promise<void> {
        if (!this.priceProvider || this.selectedSku === '' || this.selectedProductOfferReference === '') {
            this.selectedPrice = this.selectedBasePrice;
            this.renderPricePreview();

            return;
        }

        // Cleared first, as the same provider is reused across requests.
        this.priceProvider.queryParams.clear();
        this.priceProvider.queryParams.set('sku', this.selectedSku);
        this.priceProvider.queryParams.set('productOfferReference', this.selectedProductOfferReference);

        const price = (await this.priceProvider.fetch<string>().catch(() => '')).trim();
        this.selectedPrice = price === '' ? this.selectedBasePrice : Number(price);
        this.renderPricePreview();
    }

    protected renderPricePreview(): void {
        if (!this.priceContainer) {
            return;
        }

        this.priceContainer.textContent =
            this.selectedSku === '' ? '' : this.formatPrice(this.selectedPrice * this.currentQuantity);
    }

    protected formatPrice(amountInCents: number): string {
        const amount = amountInCents / CENTS_IN_UNIT;
        const currency = this.getAttribute('currency') ?? '';

        return currency === ''
            ? amount.toFixed(PRICE_DECIMAL_PLACES)
            : amount.toLocaleString(document.documentElement.lang || 'en', { style: 'currency', currency });
    }

    protected clampQuantity(quantity: number): number {
        const minimum = Number(this.quantityInput?.min) || 1;

        return Number.isFinite(quantity) ? Math.max(Math.trunc(quantity), minimum) : minimum;
    }

    protected normalizeQuantity(value: string | null | undefined): number | null {
        const trimmedValue = value?.trim() ?? '';
        const quantity = Number(trimmedValue);

        return trimmedValue === '' || !Number.isFinite(quantity) ? null : this.clampQuantity(quantity);
    }

    protected get currentQuantity(): number {
        return this.clampQuantity(Number(this.quantityInput?.value));
    }

    protected correctQuantityInputValue(): void {
        const quantity = this.normalizeQuantity(this.quantityInput?.value);

        if (!this.quantityInput || quantity === null || Number(this.quantityInput.value) === quantity) {
            return;
        }

        this.quantityInput.value = String(quantity);
        this.renderPricePreview();
    }

    protected validate(sku: string, shippingAddressKey: string, idShipmentMethod: string): boolean {
        const skuError = sku === '' ? (this.getAttribute('product-error') ?? '') : '';
        const addressError = shippingAddressKey === '' ? (this.getAttribute('shipping-address-error') ?? '') : '';
        const methodError = idShipmentMethod === '' ? (this.getAttribute('shipment-method-error') ?? '') : '';

        this.showSkuError(skuError);
        this.shipmentSelection?.showErrors(addressError, methodError);

        return skuError === '' && addressError === '' && methodError === '';
    }

    protected showSkuError(text: string): void {
        const container = this.querySelector<HTMLElement>(`.${this.jsName}__error-sku`);

        if (container) {
            container.textContent = text;
        }
    }

    protected onAdd(): void {
        const sku = this.selectedSku || (this.skuInput?.value ?? '');
        const quantity = this.currentQuantity;
        const shippingAddressKey = this.shipmentSelection?.selectedShippingAddress ?? '';
        const idShipmentMethod = this.selectedShipmentMethod;

        if (!this.validate(sku, shippingAddressKey, idShipmentMethod)) {
            return;
        }

        this.dispatchCustomEvent(
            EVENT_REVIEW_PRODUCT_PICKED,
            {
                sku,
                quantity,
                productOfferReference: this.selectedProductOfferReference,
                shippingAddressKey,
                idShippingAddress: this.shipmentSelection?.selectedIdCompanyUnitAddress ?? '',
                idShipmentMethod,
                productName: this.selectedName || sku,
                unitPrice: this.selectedPrice,
            },
            { bubbles: true },
        );
        this.reset();
    }

    protected reset(): void {
        this.clearSelection();
        this.showSkuError('');
        this.shipmentSelection?.showErrors('', '');
        this.setInputValue(this.quantityInput, '1');
        this.setInputValue(
            this.querySelector<HTMLInputElement>(`.${ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS}`),
            '',
        );
        this.offerSelect?.closest('ajax-renderer')?.replaceChildren();
        this.shipmentSelection?.reset();
    }

    protected setInputValue(input: HTMLInputElement | null, value: string): void {
        if (input) {
            input.value = value;
        }
    }

    protected get offerSelect(): HTMLSelectElement | null {
        return this.querySelector<HTMLSelectElement>(`select[name="${ReviewAddProductPicker.OFFER_REFERENCE_FIELD}"]`);
    }

    protected get selectedProductOfferReference(): string {
        return this.offerSelect?.value ?? '';
    }

    protected get selectedShipmentMethod(): string {
        return this.shipmentSelection?.selectedShipmentMethod ?? '';
    }
}
