The `Router` handles the translation from the URL to a defined route. Routes are added to the `RouteRegistry` using the
`addCollection` method:

```javascript static
routeRegistry.addCollection([
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
    {
        name: 'sulu_contact.form.detail',
        parent: 'sulu_contact.form',
        path: '/detail',
        view: 'sulu_admin.form',
        options: {
            tabTitle: 'Contacts',
        },
    },
]);
```

The `name` is just a unique identifier, the `path` is the URL for the react application, the `view` defines which
component should be rendered, which will be retrieved from the [`ViewRegistry`](#viewrenderer) by the given identifier.
Finally, `options` are additional values that can be set on the server side to influence the behavior of the react
application.

In addition to that there is an optional `parent` attribute. This can be used in order to build a hierarchy of routes.
This means that the path is prepended by the path of the parent. The routes accessible by the router's properties have
access to its relatives: The `parent` key there is a reference to its parent route, and there is also a `children` key
containing all the children of this route.

The `Router` tries to imitate the naming of [Symfony](https://symfony.com/doc/current/components/http_foundation.html),
therefore it has three different properties, which are observable, to retrieve routing parameters - `attributes` and
`query`. In addition to that there is a `route` parameter allowing to access the route options. The following examples
illustrate the values for each query based on the routes defined above:

```javascript static
// URL: #/snippets
router.route.options;        // returns {type: 'snippets'}
router.attributes;           // returns {}
router.query;                // returns {}

// URL: #/snippets?page=1
router.route.options;        // returns {type: 'snippets'}
router.attributes;           // returns {}
router.query;                // returns {page: '1'}

// URL: #/contacts/5
router.route.options;        // returns {type: 'contacts'}
router.attributes;           // returns {id: 5}
router.query;                // returns {}

// URL: #/contacts/5/detail
router.route.options;        // returns {tabTitle: 'Contacts'}
router.route.parent.options; // returns {type: 'contacts'}
router.attributes;           // returns {id: 5}
```

The service also allows to navigate using the `navigate` method. This is where the `name` of the routes are handy:

```javascript static
// route to a standard route
router.navigate('sulu_contact.form', {id: 7}, {admin: true}); // redirects to #/contacts/7?admin=true
// route to a child route (mind that there is no knowledge of the parent necessary)
router.navigate('sulu_contact.form.detail', {id: 2}, {admin: true}); // redirects to #/contacts/2/detail?admin=true
```

Something especially useful is the ability to bind any observable to a query parameter of the router. The `bindQuery`
method takes the name of the query parameter in the URL, the observable and a default value. The default value will be
set if the value in the URL is not defined. Any change in the URL will be immediately reflected in that observable and
the other way round.

```javascript static
const value = observable(1);
router.bindQuery('value', value, 'default');

// user navigates to /page?value=something
value.get(); // returns something

value.set('anything'); // will navigate to /page?value=anything

router.unbindQuery('value', value); // unbind to avoid leaking listeners
```
