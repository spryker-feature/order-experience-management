import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';

export default class RecurringOrderSelector extends Component {
    protected toggler: HTMLInputElement | null;
    protected ajaxProvider: AjaxProvider | null;
    protected contentWrapper: HTMLDivElement | null;
    protected editButton: HTMLElement | null;
    protected submitOrderButton: HTMLButtonElement | null;
    protected isSubmitLocked = false;

    protected readonly unconfirmedFormTagName = 'recurring-order-form';
    protected readonly termsCheckboxSelector = 'input[name="acceptTermsAndConditions"]';

    protected init(): void {
        this.toggler = this.querySelector<HTMLInputElement>(`.${this.jsName}__recurring-order-toggle`);
        this.ajaxProvider = this.querySelector<AjaxProvider>(`.${this.jsName}__ajax-provider`);
        this.contentWrapper = this.querySelector<HTMLDivElement>(`.${this.jsName}__wrapper`);
        this.editButton = this.querySelector<HTMLElement>(`.${this.jsName}__edit-button`);

        const submitButtonClassName = this.getAttribute('submit-button-class-name');
        this.submitOrderButton = submitButtonClassName
            ? document.querySelector<HTMLButtonElement>(`.${submitButtonClassName}`)
            : null;

        if (!this.toggler || !this.ajaxProvider) {
            return;
        }

        this.initSubmitLock();

        if (this.toggler.checked) {
            this.handleTogglerChange();
        }

        this.editButton?.addEventListener('click', () => this.handleEditChange());
        this.toggler.addEventListener('change', () => this.handleTogglerChange());
    }

    protected initSubmitLock(): void {
        if (!this.submitOrderButton || !this.contentWrapper) {
            return;
        }

        new MutationObserver(() => this.updateSubmitLockFromContent()).observe(this.contentWrapper, {
            childList: true,
            subtree: true,
        });

        new MutationObserver(() => this.enforceSubmitLock()).observe(this.submitOrderButton, {
            attributes: true,
            attributeFilter: ['disabled'],
        });

        this.submitOrderButton.form?.addEventListener(
            'submit',
            (event: Event) => this.onSummaryFormSubmit(event),
            true,
        );
    }

    protected updateSubmitLockFromContent(): void {
        this.setSubmitLock(Boolean(this.contentWrapper?.querySelector(this.unconfirmedFormTagName)));
    }

    protected setSubmitLock(isLocked: boolean): void {
        if (this.isSubmitLocked === isLocked || !this.submitOrderButton) {
            return;
        }

        this.isSubmitLocked = isLocked;

        if (isLocked) {
            this.submitOrderButton.disabled = true;

            return;
        }

        const termsCheckbox = this.submitOrderButton.form?.querySelector<HTMLInputElement>(this.termsCheckboxSelector);
        this.submitOrderButton.disabled = termsCheckbox ? !termsCheckbox.checked : false;
    }

    protected enforceSubmitLock(): void {
        if (this.isSubmitLocked && this.submitOrderButton && !this.submitOrderButton.disabled) {
            this.submitOrderButton.disabled = true;
        }
    }

    protected onSummaryFormSubmit(event: Event): void {
        if (!this.isSubmitLocked) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }

    protected handleEditChange(): void {
        this.setSubmitLock(true);

        const url = this.toggler!.dataset.editRecurrentOrderUrl ?? '';

        this.sendRequest('GET', url);
    }

    protected async handleTogglerChange(): Promise<void> {
        const isChecked = this.toggler!.checked;
        this.setSubmitLock(isChecked);

        const url = isChecked
            ? (this.toggler!.dataset.createRecurrentOrderUrl ?? '')
            : (this.toggler!.dataset.clearRecurrentOrderUrl ?? '');
        const method = isChecked ? 'GET' : 'POST';
        const body = isChecked ? undefined : this.createClearRequestBody();

        await this.sendRequest(method, url, body);

        if (!isChecked) {
            this.cleanLayout();
        }
    }

    /** A failed request is not swallowed, so it leaves the layout untouched instead of clearing it. */
    protected async sendRequest(method: string, url: string, body?: FormData): Promise<void> {
        const provider = this.ajaxProvider!;

        provider.setAttribute('method', method);
        provider.setAttribute('url', url);

        await provider.fetch(body);
    }

    protected createClearRequestBody(): FormData {
        const formData = new FormData();
        formData.append('_token', this.toggler!.dataset.clearRecurrentOrderCsrfToken ?? '');

        return formData;
    }

    protected cleanLayout(): void {
        if (!this.contentWrapper) {
            return;
        }

        this.contentWrapper.innerHTML = '';
    }
}
