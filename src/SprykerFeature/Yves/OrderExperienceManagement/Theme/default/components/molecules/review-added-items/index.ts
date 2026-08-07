import register from 'ShopUi/app/registry';

export default register(
    'review-added-items',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-added-items" */
            './review-added-items'
        ),
);
