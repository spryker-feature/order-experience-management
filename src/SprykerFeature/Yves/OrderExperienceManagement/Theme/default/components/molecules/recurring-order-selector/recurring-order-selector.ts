import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';

export default class RecurringOrderSelector extends Component {
    protected toggler: HTMLInputElement | null;
    protected ajaxProvider: AjaxProvider | null;
    protected contentWrapper: HTMLDivElement | null;
    protected editButton: HTMLElement | null;

    protected readyCallback(): void {}

    protected init(): void {
        this.toggler = this.querySelector<HTMLInputElement>(`.${this.jsName}__recurring-order-toggle`);
        this.ajaxProvider = this.querySelector<AjaxProvider>(`.${this.jsName}__ajax-provider`);
        this.contentWrapper = this.querySelector<HTMLDivElement>(`.${this.jsName}__wrapper`);
        this.editButton = this.querySelector<HTMLElement>(`.${this.jsName}__edit-button`);

        if (!this.toggler || !this.ajaxProvider) {
            return;
        }

        this.handleTogglerChange();
        this.editButton?.addEventListener('click', () => this.handleEditChange());
        this.toggler.addEventListener('change', () => this.handleTogglerChange());
    }

    protected handleEditChange(): void {
        const url = this.toggler!.dataset.editRecurrentOrderUrl ?? '';

        this.fetchWithProvider('GET', url);
    }

    protected handleTogglerChange(): void {
        const isChecked = this.toggler!.checked;
        const url = isChecked
            ? (this.toggler!.dataset.createRecurrentOrderUrl ?? '')
            : (this.toggler!.dataset.clearRecurrentOrderUrl ?? '');
        const method = isChecked ? 'GET' : 'POST';
        const body = isChecked ? undefined : this.createClearRequestBody();

        this.fetchWithProvider(method, url, body).then(() => {
            if (!isChecked) {
                this.cleanLayout();
            }
        });
    }

    protected createClearRequestBody(): FormData {
        const formData = new FormData();
        formData.append('_token', this.toggler!.dataset.clearRecurrentOrderCsrfToken ?? '');

        return formData;
    }

    protected fetchWithProvider(method: string, url: string, body?: FormData): Promise<void> {
        this.ajaxProvider!.setAttribute('method', method);
        this.ajaxProvider!.setAttribute('url', url);

        return this.ajaxProvider!.fetch(body);
    }

    protected cleanLayout(): void {
        if (!this.contentWrapper) {
            return;
        }

        this.contentWrapper.innerHTML = '';
    }
}
