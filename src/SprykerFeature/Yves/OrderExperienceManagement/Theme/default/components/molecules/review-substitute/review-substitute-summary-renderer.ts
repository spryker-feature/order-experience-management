import Component from 'ShopUi/models/component';

const CENTS_IN_UNIT = 100;

const PRICE_DECIMAL_PLACES = 2;

const ATTRIBUTE_UNIT_PRICE = 'data-unit-price';

const ATTRIBUTE_QUANTITY = 'data-quantity';

const ATTRIBUTE_LINE_TOTAL = 'data-line-total';

const EMPTY_PRICE = '0';

const COLUMN_SELECTOR = '.col';

const DEFAULT_HIDDEN_CLASS = 'is-hidden';

export interface SubstituteSummaryValues {
    sku: string;
    productName: string;
    merchantLabel: string;
    unitPrice: number;
    quantity: number;
}

export default class ReviewSubstituteSummaryRenderer {
    protected readonly nameElement: HTMLElement | null;

    protected readonly skuElement: HTMLElement | null;

    protected readonly priceElement: HTMLElement | null;

    protected readonly merchantElement: HTMLElement | null;

    protected readonly lineElement: HTMLElement | null;

    protected readonly removeButton: HTMLElement | null;

    protected readonly changeButton: HTMLElement | null;

    protected readonly appliedElements: (HTMLElement | null)[];

    protected readonly hiddenClass: string;

    protected changeButtonColumn: HTMLElement | null;

    protected changeButtonHome: { parent: HTMLElement; next: Node | null } | null = null;

    constructor(protected readonly host: Component) {
        const jsName = host.jsName;
        this.nameElement = host.querySelector(`.${jsName}__summary-name`);
        this.skuElement = host.querySelector(`.${jsName}__summary-sku`);
        this.priceElement = host.querySelector(`.${jsName}__summary-price`);
        this.merchantElement = host.querySelector(`.${jsName}__summary-merchant`);
        this.lineElement = host.querySelector(`.${jsName}__line`);
        this.removeButton = host.querySelector(`.${jsName}__remove`);
        this.changeButton = document.querySelector(`.${host.getAttribute('trigger-class') ?? ''}`);
        this.hiddenClass = host.getAttribute('hidden-class') ?? DEFAULT_HIDDEN_CLASS;
        this.appliedElements = [
            host.querySelector(`.${jsName}__applied`),
            host.querySelector(`.${jsName}__qty-wrap`),
            host.querySelector(`.${jsName}__applied-details`),
            host.querySelector(`.${jsName}__discontinued-message`),
            this.removeButton,
        ];

        this.rememberChangeButtonHome();
    }

    render(values: SubstituteSummaryValues): void {
        this.renderText(this.nameElement, values.productName);
        this.renderText(this.skuElement, values.sku === '' ? '' : `(${values.sku})`);
        this.renderText(this.priceElement, this.formatPrice(values.unitPrice * values.quantity));
        this.renderMerchantLabel(values.merchantLabel);
        this.renderLine(values.unitPrice, values.quantity);
    }

    protected formatPrice(amountInCents: number): string {
        const amount = amountInCents / CENTS_IN_UNIT;
        const currency = this.host.getAttribute('currency') ?? '';

        return currency === ''
            ? amount.toFixed(PRICE_DECIMAL_PLACES)
            : amount.toLocaleString(document.documentElement.lang || 'en', { style: 'currency', currency });
    }

    clear(): void {
        this.renderMerchantLabel('');
        this.lineElement?.setAttribute(ATTRIBUTE_UNIT_PRICE, EMPTY_PRICE);
        this.lineElement?.setAttribute(ATTRIBUTE_LINE_TOTAL, EMPTY_PRICE);
    }

    showAppliedState(): void {
        this.toggleAppliedElements(true);
        this.moveChangeButtonBeforeRemove();
        this.setChangeButtonLabel(this.host.getAttribute('change-label') ?? '');
    }

    showChooseState(): void {
        this.toggleAppliedElements(false);
        this.restoreChangeButtonHome();
        this.setChangeButtonLabel(this.host.getAttribute('select-label') ?? '');
    }

    protected renderLine(unitPrice: number, quantity: number): void {
        this.lineElement?.setAttribute(ATTRIBUTE_UNIT_PRICE, String(unitPrice));
        this.lineElement?.setAttribute(ATTRIBUTE_QUANTITY, String(quantity));
        this.lineElement?.setAttribute(ATTRIBUTE_LINE_TOTAL, String(unitPrice * quantity));
    }

    protected renderMerchantLabel(merchantLabel: string): void {
        this.renderText(this.merchantElement, merchantLabel === '' ? '' : ` · ${merchantLabel}`);
    }

    protected renderText(element: HTMLElement | null, text: string): void {
        if (element) {
            element.textContent = text;
        }
    }

    protected toggleAppliedElements(isVisible: boolean): void {
        this.appliedElements.forEach((element: HTMLElement | null) =>
            element?.classList.toggle(this.hiddenClass, !isVisible),
        );
    }

    protected setChangeButtonLabel(label: string): void {
        if (this.changeButton) {
            this.changeButton.textContent = label;
        }
    }

    protected rememberChangeButtonHome(): void {
        this.changeButtonColumn = this.changeButton?.closest<HTMLElement>(COLUMN_SELECTOR) ?? this.changeButton;

        if (this.changeButtonColumn?.parentElement) {
            this.changeButtonHome = {
                parent: this.changeButtonColumn.parentElement,
                next: this.changeButtonColumn.nextSibling,
            };
        }
    }

    protected moveChangeButtonBeforeRemove(): void {
        const removeColumn = this.removeButton?.closest(COLUMN_SELECTOR);

        if (this.changeButtonColumn && removeColumn) {
            removeColumn.before(this.changeButtonColumn);
        }
    }

    protected restoreChangeButtonHome(): void {
        if (this.changeButtonColumn && this.changeButtonHome) {
            this.changeButtonHome.parent.insertBefore(this.changeButtonColumn, this.changeButtonHome.next);
        }
    }
}
