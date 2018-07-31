The `ResourceTabs` view shows all the children of its defined route as a [`Tab`](#tab), and uses their `tabTitle`
option as the title for the tab. This is enabled by the [`Router`](#router) with handling children and parent routes
and by the [`ViewRenderer`](#viewrenderer) with nesting these routes into different views.

| Option        | Description                                                                                         |
|---------------|-----------------------------------------------------------------------------------------------------|
| resourceKey   | The resourceKey for the type of entity which will be managed by the given tabs.                     |
| locales       | The locales in which the given type of entity is available in.
