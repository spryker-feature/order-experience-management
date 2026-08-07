import register from 'ShopUi/app/registry';

export default register(
    'recurring-order-edit-form',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "recurring-order-edit-form" */
            './recurring-order-edit-form'
        ),
);
