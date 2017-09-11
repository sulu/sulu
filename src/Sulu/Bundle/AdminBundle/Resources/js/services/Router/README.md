The `Router` handles the translation from the URL to a defined route. Routes are added to the `RouteStore` using the
`add` or `addCollection` methods:

```javascript
routeStore.add({
    name: 'sulu_snippet.list',
    path: '/snippets',
    view: 'sulu_admin.list',
    options: {
        type: 'snippets',
    },
});

routeStore.addCollection([
    {
        name: 'sulu_contact.list',
        path: '/contacts',
        view: 'sulu_admin.list',
        options: {
            type: 'contacts',
        },
    },
    {
        name: 'sulu_contact.form',
        path: '/contacts/:id',
        view: 'sulu_admin.form',
        options: {
            type: 'contact',
        },
    },
]);
```

The `name` is just a unique identifier, the `path` is the URL for the react application, the `view` defines which
component should be rendered, which will be retrieved from the [`ViewStore`](#viewrenderer) by the given identifier.
Finally, `options` are additional values that can be set on the server side to influence the behavior of the react
application.

The `Router` tries to imitate the naming of [Symfony](https://symfony.com/doc/current/components/http_foundation.html),
therefore it has three different properties, which are observable, to retrieve routing parameters - `attributes` and
`query`. In addition to that there is a `route` parameter allowing to access the route options. The following examples
illustrate the values for each query based on the routes defined above:

```javascript
// URL: #/snippets
router.route.options;   // returns {type: 'snippets'}
router.attributes;      // returns {}
router.query;           // returns {}

// URL: #/snippets?page=1
router.route.options;   // returns {type: 'snippets'}
router.attributes;      // returns {}
router.query;           // returns {page: '1'}

// URL: #/contacts/5
router.route.options;   // returns {type: 'contacts'}
router.attributes;      // returns {id: 5}
router.query;           // returns {}
```

The service also allows to navigate using the `navigate` method. This is where the `name` of the routes are handy:

```javascript
const name = 'sulu_contact.form';
const attributes = {id: 7};
const query = {admin: true};

router.navigate(name, attributes, query); // redirects to #/contacts/7?admin=true
```
