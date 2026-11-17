The `Preview` sidebar is registered with the key `sulu_preview.preview`.
It shows the rendered web-page of the resource represented by the given `FormStore`.

To initialize the `Preview` for your component use following code-snippet:

```javascript static
const PageWithSidebar = withSidebar(Page, function() {
    const {
        router,
        formStore,
    } = this.props;

    return {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: router,
            formStore: formStore,
        },
    };
});
``` 

| Prop        | Description                                                                                  |
|-------------|----------------------------------------------------------------------------------------------|
| `router`    | The `Router`, used to read the attributes (locale, webspace, ...) for the preview URL.       |
| `formStore` | `ResourceFormStore` of the edited resource; changes of its data are sent to the preview and rendered. |

The `sizes` passed to `withSidebar` define the widths the sidebar can have, `medium` and `large` in the example above.
The preview renders the page in an iframe, with buttons for the device mode (`auto`, `desktop`, `tablet`,
`smartphone`), the preview URL and the preview link, so it needs a running backend and cannot be rendered in this
documentation.

The behavior is configured with static properties of the `Preview` class:

| Static              | Description                                                         |
|---------------------|---------------------------------------------------------------------|
| `mode`              | Default device mode, one of `auto`, `desktop`, `tablet`, `smartphone`. |
| `debounceDelay`     | Milliseconds to wait after a change before the preview is updated.  |
| `audienceTargeting` | Shows the audience targeting group selection if `true`.             |
