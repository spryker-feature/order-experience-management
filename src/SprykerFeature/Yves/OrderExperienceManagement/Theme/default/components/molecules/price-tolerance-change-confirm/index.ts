import register from 'ShopUi/app/registry';

export default register(
    'price-tolerance-change-confirm',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "price-tolerance-change-confirm" */
            './price-tolerance-change-confirm'
        ),
);
