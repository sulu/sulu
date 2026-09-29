This view decorates the [`ResourceTabs`](#resourcetabs) view for the settings of a webspace. It takes the locales from
the webspace in the `webspace` attribute of the route. There is only one setting per webspace, so the key of the
webspace is also its `id`, and no title is shown.

The `id` attribute is set to the selected webspace whenever the view is created, because attributes passed to the router
win over derived ones, and it is not derived again when only the webspace is changed.
