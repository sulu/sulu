The `ResourceTabs` view shows all the children of its defined route as a [`Tab`](#tab), and uses their `tabTitle`
option as the title for the tab. This is enabled by the [`Router`](#router) with handling children and parent routes
and by the [`ViewRenderer`](#viewrenderer) with nesting these routes into different views.

It will use the `tabOrder` option of its child routes to determine how to order the tabs. The other important option is
the `tabPriority` option, which allows to define which tab should be opened by default. It will open the tab with the
highest priority.

| Option        | Description                                                                                         |
|---------------|-----------------------------------------------------------------------------------------------------|
| locales       | The locales in which the given type of entity is available in.
| resourceKey   | The resourceKey for the type of entity which will be managed by the given tabs.                     |
| titleProperty | The property of the object in the ResourceStore being used in the headline of the tab.              |

Besides the options listed above, `tabBadges` works like in the [`Tabs`](#tabs) view. The `tabCondition` option of a
child route is a JEXL expression evaluated with the data of the loaded resource, the tab is hidden if it is falsy. If
`locales` is set, a `locale` router attribute is bound to the `ResourceStore`. The `locales`, the `resourceStore` and
the `title` (value of the `titleProperty` in the resource) are passed to the view of the selected tab.
