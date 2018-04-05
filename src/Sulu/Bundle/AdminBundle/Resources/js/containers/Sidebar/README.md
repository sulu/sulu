The `Sidebar` is a configurable component which serves as a wrapper for multiple other component types. The
configuration can be set by using the `withSidebar` function which takes a React component as the first and a 
callback which returns the configuration object as the second parameter. In most cases the given component would be some
kind of a page or view component in which communicates with the `Sidebar`.

The configuration object describes how the Sidebar should behave and which view should be rendered inside.

Here is a basic usage example how `withSidebar` could be used:

```javascript static
const PageWithSidebar = withSidebar(Page, function() {
    return {
        view: 'test',
        props: {
            title: 'world',
        },
    };
});
```

In the above example a new sidebar-view was registered, used beside a component and passed some `props`.

Additionally you can pass which Sidebar `sizes` are supported by the component (one or more of `small`, `medium` or 
`large`) and a `defaultSize` when the sidebar will be initialized.
