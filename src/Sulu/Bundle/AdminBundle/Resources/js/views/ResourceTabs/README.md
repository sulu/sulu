The `ResourceTabs` view shows all the children of its defined route as a [`Tab`](#tab), and uses their `tabTitle`
option as the title for the tab. This is enabled by the [`Router`](#router) with handling children and parent routes
and by the [`ViewRenderer`](#viewrenderer) with nesting these routes into different views.

It will use the `tabOrder` option of its child routes to determine how to order the tabs. The other important option is
the `tabPriority` option, which allows to define which tab should be opened by default. It will open the tab with the
highest priority.
