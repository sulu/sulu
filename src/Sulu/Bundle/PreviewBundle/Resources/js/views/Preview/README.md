The `Preview` sidebar-view is registered with the key `sulu_preview.preview`.
It shows the rendered web-page of the resource represented by the given resource-store.

To initialize the `Preview` for your component use following code-snippet:

```javascript static
const PageWithSidebar = withSidebar(Page, function() {
    const {
        router,
        resourceStore,
    } = this.props;

    return {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: router,
            resourceStore: resourceStore,
        },
    };
});
``` 
