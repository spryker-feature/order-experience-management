import register from 'ShopUi/app/registry';

export default register(
    'review-summary-banner-price',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-sumary-banner-price" */
            './review-sumary-banner-price'
        ),
);
