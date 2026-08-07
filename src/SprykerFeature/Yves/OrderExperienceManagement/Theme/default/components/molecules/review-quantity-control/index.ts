import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-quantity-control',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-quantity-control" */
            './review-quantity-control'
        ),
);
