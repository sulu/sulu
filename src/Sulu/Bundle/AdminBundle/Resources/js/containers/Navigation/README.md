The `Navigation` container uses the `NavigationRegistry` to load all navigation items and render the navigation component with all items.

Set navigation items in the `NavigationRegistry` and rendering it using the `Navigation` is shown in the following example:

```javascript
const Navigation = require('./Navigation').default;
const navigationRegistry = require('./registries/NavigationRegistry').default;
navigationRegistry.clear(); // Just to make sure the NavigationRegistry is empty, not needed in a real world application

const items = [
    {
        "label": "Webspaces",
        "icon": "su-webspace",
        "mainRoute": "sulu_page.webspaces",
        "disabled": false,
        "id": "5aba0a9d7a5e3174614828",
        "childRoutes": [
          "sulu_page.webspaces",
          "sulu_page.page_add_form",
          "sulu_page.page_add_form.detail",
          "sulu_page.page_edit_form",
          "sulu_page.page_edit_form.detail"
        ]
      },
      {
        "label": "Schnipsel",
        "icon": "su-paper",
        "action": "snippet/snippets",
        "mainRoute": "sulu_snippet.datagrid",
        "disabled": false,
        "id": "5aba0a9d7a5ef415448250",
        "childRoutes": [
          "sulu_snippet.datagrid",
          "sulu_snippet.add_form",
          "sulu_snippet.add_form.detail",
          "sulu_snippet.edit_form",
          "sulu_snippet.edit_form.detail",
          "sulu_snippet.edit_form.taxonomies"
        ]
      },
      {
        "label": "Medien",
        "icon": "su-image",
        "mainRoute": "sulu_media.overview",
        "disabled": false,
        "id": "5aba0a9d7a5f7279240523",
        "childRoutes": [
          "sulu_media.overview"
        ]
      },
      {
        "label": "Kontakte",
        "icon": "fa-user",
        "disabled": false,
        "id": "5aba0a9d7a5ff533782840",
        "items": [
          {
            "label": "Personen",
            "mainRoute": "sulu_contact.contacts_datagrid",
            "disabled": false,
            "id": "5aba0a9d7a608673818690",
            "childRoutes": [
              "sulu_contact.contacts_datagrid",
              "sulu_contact.add_form",
              "sulu_contact.add_form.detail",
              "sulu_contact.edit_form",
              "sulu_contact.edit_form.detail"
            ]
          },
          {
            "label": "Organisationen",
            "mainRoute": "sulu_contact.accounts_datagrid",
            "disabled": false,
            "id": "5aba0a9d7a610927910329",
            "childRoutes": [
              "sulu_contact.accounts_datagrid"
            ]
          }
        ]
      },
      {
        "label": "Einstellungen",
        "icon": "su-cog",
        "disabled": false,
        "id": "5aba0a9d7a619926740941",
        "items": [
          {
            "label": "Benutzerrollen",
            "mainRoute": "sulu_security.datagrid",
            "disabled": false,
            "id": "5aba0a9d7a621158100794",
            "childRoutes": [
              "sulu_security.datagrid"
            ]
          },
          {
            "label": "Tags",
            "mainRoute": "sulu_tag.datagrid",
            "disabled": false,
            "id": "5aba0a9d7a629138404840",
            "childRoutes": [
              "sulu_tag.datagrid"
            ]
          }
        ]
      }
];
navigationRegistry.set(items);

// instead of this mocked Router you would usually use a real one
const router = {
    attributes: {
        content: 'Some trivial content!',
    },
    route: {
       view: 'view',
       name: 'test',
   },
    navigate: (value) => { console.log(`Router would navigate to ${value}`)}
};

const handleNavigate = (value) => {
    alert(`You clicked on item ${value}`);
};

<Navigation router={router} onNavigate={handleNavigate} />
```
