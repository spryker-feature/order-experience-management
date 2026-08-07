import register from 'ShopUi/app/registry';

export default register(
    'review-change-summary',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-change-summary" */
            './review-change-summary'
        ),
);
