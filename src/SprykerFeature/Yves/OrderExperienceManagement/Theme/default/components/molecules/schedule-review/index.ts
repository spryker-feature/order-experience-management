import register from 'ShopUi/app/registry';

export default register(
    'schedule-review',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "schedule-review" */
            './schedule-review'
        ),
);
