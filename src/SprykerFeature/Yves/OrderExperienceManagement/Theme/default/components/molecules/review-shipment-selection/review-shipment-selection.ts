import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';

export const EVENT_SHIPMENT_METHODS_UPDATED = 'shipmentMethodsUpdated';
export const EVENT_SHIPMENT_METHOD_CHANGE = 'shipmentMethodChange';

export interface ProductContext {
    sku: string;
    productOfferReference: string;
}

export default class ReviewShipmentSelection extends Component {
    protected static readonly SHIPMENT_ADDRESS_FIELD = 'shipment_address';
    protected static readonly SHIPMENT_METHOD_FIELD = 'shipment_method';
    protected sku = '';
    protected productOfferReference = '';
    protected pendingShipmentMethod = '';

    protected init(): void {
        this.addEventListener('change', (event: Event) => this.onChange(event));
    }

    protected onChange(event: Event): void {
        const targetName = (event.target as HTMLElement | null)?.getAttribute('name');

        if (targetName === ReviewShipmentSelection.SHIPMENT_ADDRESS_FIELD) {
            this.refreshMethods();

            return;
        }

        if (targetName === ReviewShipmentSelection.SHIPMENT_METHOD_FIELD) {
            this.dispatchCustomEvent(EVENT_SHIPMENT_METHOD_CHANGE);
        }
    }

    async refreshMethods(context?: ProductContext): Promise<void> {
        if (context) {
            if (context.sku !== this.sku) {
                this.pendingShipmentMethod = '';
            }

            this.sku = context.sku;
            this.productOfferReference = context.productOfferReference;
        }

        const provider = this.querySelector<AjaxProvider>(`.${this.jsName}__methods-provider`);

        if (!provider) {
            return;
        }

        if (this.sku === '' || this.selectedShippingAddress === '') {
            this.clearMethods();

            return;
        }

        // Cleared first, as the same provider is reused across requests.
        provider.queryParams.clear();
        provider.queryParams.set('sku', this.sku);
        provider.queryParams.set('productOfferReference', this.productOfferReference);
        provider.queryParams.set('shippingAddressKey', this.selectedShippingAddress);

        // A failed request must leave the rendered methods alone rather than clear the layout.
        await provider.fetch<string>().catch(() => '');

        this.dispatchCustomEvent(EVENT_SHIPMENT_METHODS_UPDATED);
    }

    clearMethods(): void {
        const container = this.methodsContainer;

        if (container) {
            container.innerHTML = '';
        }

        this.dispatchCustomEvent(EVENT_SHIPMENT_METHODS_UPDATED);
    }

    restore(context: ProductContext, shippingAddressKey: string, idShipmentMethod: string): void {
        const addressSelect = this.addressSelect;

        if (addressSelect) {
            addressSelect.value = shippingAddressKey;
        }

        this.sku = context.sku;
        this.productOfferReference = context.productOfferReference;
        this.pendingShipmentMethod = idShipmentMethod;
        this.refreshMethods();

        const methodsContainer = this.methodsContainer;

        if (this.applyPendingMethod() || !methodsContainer) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (this.applyPendingMethod()) {
                observer.disconnect();
            }
        });

        observer.observe(methodsContainer, { childList: true, subtree: true });
    }

    protected applyPendingMethod(): boolean {
        if (this.pendingShipmentMethod === '') {
            return true;
        }

        const methodSelect = this.methodSelect;
        const hasOption = Array.from(methodSelect?.options ?? []).some(
            (option: HTMLOptionElement) => option.value === this.pendingShipmentMethod,
        );

        if (!methodSelect || !hasOption) {
            return false;
        }

        methodSelect.value = this.pendingShipmentMethod;

        return true;
    }

    reset(): void {
        this.sku = '';
        this.productOfferReference = '';
        this.pendingShipmentMethod = '';

        const addressSelect = this.addressSelect;

        if (addressSelect) {
            addressSelect.value = '';
        }

        this.clearMethods();
    }

    showErrors(addressError: string, methodError: string): void {
        this.setErrorText(`.${this.jsName}__error-address`, addressError);
        this.setErrorText(`.${this.jsName}__error-method`, methodError);
    }

    protected setErrorText(selector: string, text: string): void {
        const container = this.querySelector<HTMLElement>(selector);

        if (container) {
            container.textContent = text;
        }
    }

    get selectedShippingAddress(): string {
        return this.addressSelect?.value ?? '';
    }

    get selectedIdCompanyUnitAddress(): string {
        return this.addressSelect?.selectedOptions[0]?.dataset.idCompanyUnitAddress ?? '';
    }

    get selectedShipmentMethod(): string {
        return this.methodSelect?.value ?? '';
    }

    protected get addressSelect(): HTMLSelectElement | null {
        return this.querySelector<HTMLSelectElement>(
            `select[name="${ReviewShipmentSelection.SHIPMENT_ADDRESS_FIELD}"]`,
        );
    }

    protected get methodSelect(): HTMLSelectElement | null {
        return this.querySelector<HTMLSelectElement>(`select[name="${ReviewShipmentSelection.SHIPMENT_METHOD_FIELD}"]`);
    }

    protected get methodsContainer(): HTMLElement | null {
        return this.querySelector<HTMLElement>(`.${this.jsName}__methods`);
    }
}
