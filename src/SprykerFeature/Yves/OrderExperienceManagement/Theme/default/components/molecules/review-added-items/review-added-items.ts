import Component from 'ShopUi/models/component';

const SKU_FIELD_SELECTOR = 'input[name$="[sku]"]';

const ENTRY_KEY_PATTERN = /\[([^\]]+)]\[sku]$/;

const PROTOTYPE_ATTRIBUTE = 'data-prototype';

const PROTOTYPE_NAME_PATTERN = /__name__/g;

/**
 * Write requests for the `addedItems` collection. They are listened for on document rather than on the element,
 * because the collection is rendered inside review-add-product while the substitute flow lives in a separate
 * review-flagged-items subtree, so a bubbled event never descends into this element.
 */
export const EVENT_ADDED_ITEM_WRITE = 'addedItemWrite';

export const EVENT_ADDED_ITEM_UPDATE = 'addedItemUpdate';

export const EVENT_ADDED_ITEM_REMOVE = 'addedItemRemove';

export interface AddedItemWriteDetail {
    entryKey: string;
    values: AddedItemEntryValues;
}

export interface AddedItemUpdateDetail {
    entryKey: string;
    values: Partial<AddedItemEntryValues>;
}

export interface AddedItemRemoveDetail {
    entryKey: string;
}

/**
 * Field values of a single `addedItems` collection entry. The property names are the form field names, so the
 * shipment fields can be omitted to leave the form defaults in place. The name and the price are carried along to
 * redraw the visible line after a reload; the server resolves the price it charges on its own.
 */
export interface AddedItemEntryValues {
    sku: string;
    quantity: number;
    productOfferReference: string;
    shippingAddressKey?: string;
    idShippingAddress?: string;
    idShipmentMethod?: string;
    productName?: string;
    unitPrice?: number;
}

export default class ReviewAddedItems extends Component {
    /**
     * Listeners are bound on upgrade rather than in init(), because components are mounted in registry order and a
     * writer's init() may well run before this one's — the substitute flow writes its stored entry from init().
     */
    protected connectedCallback(): void {
        document.addEventListener(EVENT_ADDED_ITEM_WRITE, (event: Event) => {
            const { entryKey, values } = (event as CustomEvent<AddedItemWriteDetail>).detail;
            this.writeEntry(entryKey, values);
        });

        document.addEventListener(EVENT_ADDED_ITEM_UPDATE, (event: Event) => {
            const { entryKey, values } = (event as CustomEvent<AddedItemUpdateDetail>).detail;
            this.updateEntry(entryKey, values);
        });

        document.addEventListener(EVENT_ADDED_ITEM_REMOVE, (event: Event) => {
            this.removeEntry((event as CustomEvent<AddedItemRemoveDetail>).detail.entryKey);
        });
    }

    getEntryKeys(): string[] {
        return this.getSkuFields()
            .map((skuField: HTMLInputElement) => skuField.name.match(ENTRY_KEY_PATTERN)?.[1] ?? null)
            .filter((entryKey: string | null): entryKey is string => entryKey !== null);
    }

    hasEntry(entryKey: string): boolean {
        return this.findEntry(entryKey) !== null;
    }

    readEntry(entryKey: string): Required<AddedItemEntryValues> | null {
        const entryElement = this.findEntry(entryKey);

        if (!entryElement) {
            return null;
        }

        return {
            sku: this.readField(entryElement, 'sku'),
            quantity: Number(this.readField(entryElement, 'quantity')) || 0,
            productOfferReference: this.readField(entryElement, 'productOfferReference'),
            shippingAddressKey: this.readField(entryElement, 'shippingAddressKey'),
            idShippingAddress: this.readField(entryElement, 'idShippingAddress'),
            idShipmentMethod: this.readField(entryElement, 'idShipmentMethod'),
            productName: this.readField(entryElement, 'productName'),
            unitPrice: Number(this.readField(entryElement, 'unitPrice')) || 0,
        };
    }

    /** Replaces the entry under the same key, so re-picking a substitute does not leave the previous one behind. */
    protected writeEntry(entryKey: string, values: AddedItemEntryValues): void {
        const collectionContainer = this.collectionContainer;
        const prototype = collectionContainer?.getAttribute(PROTOTYPE_ATTRIBUTE);

        if (!collectionContainer || !prototype) {
            return;
        }

        this.removeEntry(entryKey);

        const entryElement = document.createElement('div');
        const entryMarkup = prototype.replace(PROTOTYPE_NAME_PATTERN, entryKey);
        const parsedBody = new DOMParser().parseFromString(entryMarkup, 'text/html').body;
        entryElement.append(...parsedBody.childNodes);

        this.writeFields(entryElement, values);
        collectionContainer.appendChild(entryElement);
    }

    protected updateEntry(entryKey: string, values: Partial<AddedItemEntryValues>): void {
        const entryElement = this.findEntry(entryKey);

        if (entryElement) {
            this.writeFields(entryElement, values);
        }
    }

    protected removeEntry(entryKey: string): void {
        this.findEntry(entryKey)?.remove();
    }

    protected findEntry(entryKey: string): HTMLElement | null {
        const collectionContainer = this.collectionContainer;
        const skuField = collectionContainer?.querySelector<HTMLInputElement>(`input[name$="[${entryKey}][sku]"]`);

        if (!collectionContainer || !skuField) {
            return null;
        }

        let entryElement: HTMLElement = skuField;

        while (
            entryElement.parentElement &&
            entryElement.parentElement !== collectionContainer &&
            entryElement.parentElement.querySelectorAll(SKU_FIELD_SELECTOR).length === 1
        ) {
            entryElement = entryElement.parentElement;
        }

        return entryElement;
    }

    protected writeFields(entryElement: HTMLElement, values: Partial<AddedItemEntryValues>): void {
        Object.entries(values).forEach(([fieldName, value]: [string, string | number | undefined]) => {
            const field = entryElement.querySelector<HTMLInputElement>(`input[name$="[${fieldName}]"]`);

            if (field) {
                field.value = value === undefined ? '' : String(value);
            }
        });
    }

    protected readField(entryElement: HTMLElement, fieldName: string): string {
        return entryElement.querySelector<HTMLInputElement>(`input[name$="[${fieldName}]"]`)?.value ?? '';
    }

    protected getSkuFields(): HTMLInputElement[] {
        return Array.from(this.collectionContainer?.querySelectorAll<HTMLInputElement>(SKU_FIELD_SELECTOR) ?? []);
    }

    /** Resolved on access, as hosts read the collection from their own init() before this one has run. */
    protected get collectionContainer(): HTMLElement | null {
        return this.querySelector<HTMLElement>(`.${this.jsName}__collection`);
    }
}
