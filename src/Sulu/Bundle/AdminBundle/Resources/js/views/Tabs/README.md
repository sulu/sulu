The `Tabs` view shows all the children of its defined route as a [`Tab`](#tab), and uses their `tabTitle` option as the
title for the tab. This is enabled by the [`Router`](#router) with handling children and parent routes and by the
[`ViewRenderer`](#viewrenderer) with nesting these routes into different views.

| Option / prop    | Description                                                                                      |
|------------------|--------------------------------------------------------------------------------------------------|
| `tabTitle`       | Route option of each child route, translated and used as title of its tab.                       |
| `tabOrder`       | Route option of each child route, tabs are ordered ascending by it.                              |
| `tabBadges`      | Route option of each child route, an object of badge configurations (`routeName`, `dataPath`, `requestParameters`, `routerAttributesToRequest`, `visibleCondition`) rendered by the [`Badge`](#badge) container next to the title. |
| `disableTabGap`  | Route option of each child route, removes the gap below the tabs of a nested `Tabs` view.        |
| `title`          | Optional prop, shown as headline above the tabs.                                                 |
| `header`         | Optional prop, a node rendered above the content of the selected tab.                            |

The tabs are only displayed for the root view or if there is more than one child route.
