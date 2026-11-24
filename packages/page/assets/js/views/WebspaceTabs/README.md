The `WebspaceTabs` view acts as a decorator for the [`Tabs` view](#tabs-1). In addition to rendering the tab itself it
will display a [`WebspaceSelect`](#webspaceselect), based on which a webspace can be chosen. This webspace will then be
passed along with the `webspaceKey` observable to the `children` function, so that the child view can use this
information.

A child route can set the `tabCondition` option, a [Jexl](https://github.com/TomFrost/jexl) expression which is
evaluated with the selected webspace as `__webspace`. The tab of the child is only shown if it matches, e.g.
`__webspace.key == "example"` shows it only for the `example` webspace.
