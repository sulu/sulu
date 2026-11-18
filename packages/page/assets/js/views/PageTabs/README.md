This view decorates the [`ResourceTabs`](#resourcetabs) view in order to set the locales differently. Instead of the
locales being passed using the options from the defined route, it will be loaded using the `WebspaceStore` and the
`webspace` attribute of the route.

It then overrides the locales of the [`ResourceTabs`](#resourcetabs) using `props`, and pass all the other props as
well. This way it behaves exactly the same, except for the fact that it loads the `locales` using an instance of a
`webspace`.

The title is taken from the `title` property of the resource, unless the route sets another `titleProperty` option.

A resource which exists exactly once per webspace, like the settings of a webspace, sets the `idFromWebspace` option of
the route. The `id` attribute is then the key of the webspace, and it is set again whenever the view is created, because
attributes passed to the router win over derived ones, and it is not derived again when only the webspace is changed.
