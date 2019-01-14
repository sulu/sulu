This view decorates the [`ResourceTabs`](#resourcetabs) view in order to set the locales differently. Instead of the
locales being passed using the options from the defined route, it will be loaded using the `WebspaceStore` and the
`webspace` attribute of the route.

It then overrides the locales of the [`ResourceTabs`](#resourcetabs) using `props`, and pass all the other props as
well. This way it behaves exactly the same, except for the fact that it loads the `locales` using an instance of a
`webspace`.
