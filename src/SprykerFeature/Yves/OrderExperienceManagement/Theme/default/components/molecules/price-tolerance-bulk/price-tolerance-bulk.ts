import Component from 'ShopUi/models/component';
import { EVENT_SELECTION_CHANGE } from 'ShopUi/components/molecules/advanced-table-checkbox-cell/advanced-table-checkbox-cell';
import PriceToleranceBulkBar, {
    EVENT_BULK_APPLY,
    EVENT_BULK_TURN_OFF,
} from '../price-tolerance-bulk-bar/price-tolerance-bulk-bar';

const DEFAULT_TOLERANCE_DECIMALS = 2;
const PERCENT_DENOMINATOR = 100;
const DEFAULT_FRACTION_DIGITS = 2;
const DECIMAL_BASE = 10;
const CHECKBOX_SELECTOR = '.js-advanced-table-checkbox-cell__input--select';
const NO_TRIGGER_PLACEHOLDER = '–';
const ORDER_TOTAL_ONLY_MODIFIER = 'order-total-only-mode';

export default class PriceToleranceBulk extends Component {
    protected bar: PriceToleranceBulkBar | null;
    protected summary: HTMLElement | null;
    protected priceLimitHolderTemplate: HTMLTemplateElement | null;
    protected rows: HTMLElement[];
    protected moneyFormatter: Intl.NumberFormat | null;
    protected currencyFractionDigits: number;
    protected toleranceScale: number;
    protected isSelectionRefreshScheduled = false;

    protected init(): void {
        this.bar = this.querySelector<PriceToleranceBulkBar>(`.${this.jsName}__bar`);
        this.summary = this.querySelector(`.${this.jsName}__summary`);
        this.priceLimitHolderTemplate = this.querySelector<HTMLTemplateElement>(
            `template[data-id="${this.jsName}__price-limit-holder"]`,
        );
        this.rows = Array.from(this.querySelectorAll<HTMLElement>(`.${this.jsName}__row`));

        this.initMoneyFormatter();
        this.initToleranceScale();
        this.mapEvents();
        this.handeToleranceInput();
        this.observeOrderTotalOnlyMode();
        this.refreshSelection();
        this.rows.forEach((row: HTMLElement) => this.refreshRow(row));
        this.refreshSummary();
    }

    protected initToleranceScale(): void {
        const decimals = this.rows.map((row: HTMLElement) => this.getRowTolerance(row)?.dataset.scale).find(Boolean);

        this.toleranceScale = DECIMAL_BASE ** (decimals === undefined ? DEFAULT_TOLERANCE_DECIMALS : Number(decimals));
    }

    protected initMoneyFormatter(): void {
        try {
            this.moneyFormatter = new Intl.NumberFormat(document.documentElement.lang || undefined, {
                style: 'currency',
                currency: this.getAttribute('currency-iso-code') ?? '',
            });
            this.currencyFractionDigits =
                this.moneyFormatter.resolvedOptions().maximumFractionDigits ?? DEFAULT_FRACTION_DIGITS;
        } catch {
            this.moneyFormatter = null;
            this.currencyFractionDigits = DEFAULT_FRACTION_DIGITS;
        }
    }

    protected mapEvents(): void {
        this.addEventListener(EVENT_SELECTION_CHANGE, () => this.scheduleSelectionRefresh());
        this.bar?.addEventListener(EVENT_BULK_APPLY, ((event: CustomEvent<string>) =>
            this.setSelected(event.detail)) as EventListener);
        this.bar?.addEventListener(EVENT_BULK_TURN_OFF, () => this.setSelected('0'));
    }

    protected handeToleranceInput() {
        this.rows.forEach((row: HTMLElement) =>
            this.getRowTolerance(row)?.addEventListener('input', () => {
                this.refreshRow(row);
                this.refreshSummary();
            }),
        );
    }

    protected observeOrderTotalOnlyMode(): void {
        this.syncOrderTotalOnlyMode();
        new MutationObserver(() => this.syncOrderTotalOnlyMode()).observe(this, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    protected syncOrderTotalOnlyMode(): void {
        const isOrderTotalOnly = this.classList.contains(`${this.name}--${ORDER_TOTAL_ONLY_MODIFIER}`);

        if (isOrderTotalOnly) {
            this.toggleAll(false);
        }

        this.rows.forEach((row: HTMLElement) => {
            const toleranceInput = this.getRowTolerance(row);
            const checkbox = this.getRowCheckbox(row);

            if (toleranceInput) {
                toleranceInput.disabled = isOrderTotalOnly;
            }

            if (checkbox) {
                checkbox.disabled = isOrderTotalOnly;
            }
        });
    }

    protected getRowCheckbox(row: HTMLElement): HTMLInputElement | null {
        return row.querySelector(CHECKBOX_SELECTOR);
    }

    protected getRowTolerance(row: HTMLElement): HTMLInputElement | null {
        return row.querySelector(`.${this.jsName}__tolerance`);
    }

    protected getSelectedRows(): HTMLElement[] {
        return this.rows.filter((row: HTMLElement) => this.getRowCheckbox(row)?.checked);
    }

    protected toggleAll(isChecked: boolean): void {
        this.rows.forEach((row: HTMLElement) => {
            const checkbox = this.getRowCheckbox(row);

            if (checkbox && checkbox.checked !== isChecked) {
                checkbox.checked = isChecked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });

        this.scheduleSelectionRefresh();
    }

    protected setSelected(value: string): void {
        this.getSelectedRows().forEach((row: HTMLElement) => {
            const toleranceInput = this.getRowTolerance(row);

            if (toleranceInput) {
                toleranceInput.value = value;
                this.refreshRow(row);
            }
        });

        this.refreshSummary();
    }

    protected scheduleSelectionRefresh(): void {
        if (this.isSelectionRefreshScheduled) {
            return;
        }

        this.isSelectionRefreshScheduled = true;
        requestAnimationFrame(() => {
            this.isSelectionRefreshScheduled = false;
            this.refreshSelection();
        });
    }

    protected refreshSelection(): void {
        const selectedCount = this.getSelectedRows().length;
        customElements.whenDefined('price-tolerance-bulk-bar').then(() => this.bar?.setCount(selectedCount));
    }

    protected refreshRow(row: HTMLElement): void {
        const triggerCell = row.querySelector(`.${this.jsName}__trigger`);
        const scaledTolerance = this.parseScaledTolerance(this.getRowTolerance(row)?.value ?? '');

        if (!triggerCell) {
            return;
        }

        if (scaledTolerance === null) {
            triggerCell.textContent = NO_TRIGGER_PLACEHOLDER;

            return;
        }

        if (scaledTolerance === 0) {
            triggerCell.textContent = this.getAttribute('any-increase-label') ?? '';

            return;
        }

        const unitPrice = Number(row.dataset.unitPrice);
        const threshold = this.calculateThreshold(unitPrice, scaledTolerance);
        const priceLimitHolder = this.priceLimitHolderTemplate?.content.firstElementChild?.cloneNode(true);

        if (!priceLimitHolder) {
            triggerCell.textContent = this.formatMoney(threshold);

            return;
        }

        priceLimitHolder.textContent = this.formatMoney(threshold);
        triggerCell.replaceChildren(priceLimitHolder);
    }

    protected calculateThreshold(unitPrice: number, scaledTolerance: number): number {
        const denominator = PERCENT_DENOMINATOR * this.toleranceScale;
        const scaledPrice = unitPrice * (denominator + scaledTolerance);

        return (scaledPrice - (scaledPrice % denominator)) / denominator;
    }

    protected parseScaledTolerance(value: string): number | null {
        const tolerance = Number(value.trim());

        if (value.trim() === '' || !Number.isFinite(tolerance) || tolerance < 0) {
            return null;
        }

        return Math.round(tolerance * this.toleranceScale);
    }

    protected formatMoney(minorUnits: number): string {
        const amount = minorUnits / DECIMAL_BASE ** this.currencyFractionDigits;

        return this.moneyFormatter ? this.moneyFormatter.format(amount) : amount.toFixed(this.currencyFractionDigits);
    }

    protected refreshSummary(): void {
        if (!this.summary) {
            return;
        }

        let withTolerance = 0;
        let reviewingAnyIncrease = 0;

        this.rows.forEach((row: HTMLElement) => {
            const scaledTolerance = this.parseScaledTolerance(this.getRowTolerance(row)?.value ?? '');

            if (scaledTolerance === null) {
                return;
            }

            if (scaledTolerance > 0) {
                withTolerance++;

                return;
            }

            reviewingAnyIncrease++;
        });

        this.summary.textContent = (this.getAttribute('summary-label') ?? '')
            .replace('%with%', String(withTolerance))
            .replace('%any%', String(reviewingAnyIncrease));
    }
}
