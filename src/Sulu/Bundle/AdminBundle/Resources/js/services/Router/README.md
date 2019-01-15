The `Router` handles the translation from the URL to a defined route. Routes are added to the `RouteRegistry` using the
`addCollection` method:

```javascript static
routeRegistry.addCollection([
    {
        name: 'sulu_contact.datagrid',
        path: '/contacts',
        view: 'sulu_admin.datagrid',
        options: {
            type: 'contacts',
        },
        attributeDefaults: {},
    },
    {
        name: 'sulu_contact.form',
        path: '/contacts/:locale/:id',
        view: 'sulu_admin.form',
        options: {
            type: 'contact',
        },
        attributeDefaults: {
            locale: 'en',
        },
    },
    {
        name: 'sulu_contact.form.details',
        parent: 'sulu_contact.form',
        path: '/details',
        view: 'sulu_admin.form',
        options: {
            tabTitle: 'Contacts',
        },
        attributeDefaults: {},
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

The router also has an `attributes` property, which allows to access the parameters defined in the URL (identified by a
colon in the path) and the query parameters resp. search string of the URL. There is also the `route` propery on the
router, which allows to access its options.

```javascript static
// URL: #/contacts
router.route.options;        // returns {type: 'contacts'}
router.attributes;           // returns {}
router.query;                // returns {}

// URL: #/contacts?page=1
router.route.options;        // returns {type: 'contacts'}
router.attributes;           // returns {page: '1'}

// URL: #/contacts/5
router.route.options;        // returns {type: 'contacts'}
router.attributes;           // returns {id: 5}

// URL: #/contacts/5/detail
router.route.options;        // returns {tabTitle: 'Contacts'}
router.route.parent.options; // returns {type: 'contacts'}
router.attributes;           // returns {id: 5}
```

The service also allows to navigate using the `navigate` method. This is where the `name` of the routes are handy:

```javascript static
// route to a standard route
router.navigate('sulu_contact.form', {id: 7, admin: true}); // redirects to #/contacts/7?admin=true
// route to a child route (mind that there is no knowledge of the parent necessary)
router.navigate('sulu_contact.form.details', {id: 2, admin: true}); // redirects to #/contacts/2/detail?admin=true
```

Instead of `navigate` you can also use the `restore` function. `restore` takes the same parameters, but the difference
is that the attributes and query parameter will be merged with the previous values of this route. So this is especially
useful when implementing functionality like a `back` button, since using it will e.g. bring you back to the same page
of a paginated list.

```javascript static
// navigates to #/contacts?page=3
router.navigate('sulu_contact.datagrid', {page: 3, locale: 'en'});

// navigates to #/contacts/7/detail?locale=en
router.navigate('sulu_contact.form.details', {id: 7, locale: 'en'});

// navigates to #/contacts/7/detail?locale=de
router.navigate('sulu_contact.form.details', {id: 7, locale: 'de'});

// navigates to #/contacts?page=3&locale=de
router.restore('sulu_contact.datagrid', locale: 'de'});
```

Something especially useful is the ability to bind any observable to a query parameter of the router. The `bind` method
takes the name of a route attribute in the URL, the observable and a default value. The default value will be set if
the value in the URL is not defined. Any change in the URL will be immediately reflected in that observable and the
other way round.

```javascript static
const value = observable(1);
router.bind('value', value, 'default');

// user navigates to /page?value=something
value.get(); // returns something

value.set('anything'); // will navigate to /page?value=anything

router.unbind('value', value); // unbind to avoid leaking listeners
```

Mind that the bindings will be cleared on every `navigate` call. This is necessary to avoid superfluous updates because
of wrong update on observers.

In addition to the normal navigating there are the `updateAttributesHooks`. These hooks can be added using the
`addUpdateAttributesHook` function. These hooks will get the route to which is currently navigated as its first
argument, and the hook can decide using that argument what attributes it will return. These attributes will be merged
with the attribute passed to the `navigate` or `restore` call, whereby the arguments from the function call take
precedence. Finally the attribute defaults will only be applied if the given attribute has not been in either of the
previous two described ways. So the priorities of the available attributes is as follows:

- arguments from `navigate` or `restore` call
- return value from `updateAttributesHooks`
- attribute defaults

If the attribute is mandatory (i.e. in the path and not a query parameter) and it is not passed in any of these ways,
then an error will be thrown.

See the following example for a better understanding.

```javascript static
routeRegistry.addCollection([
    {
        name: 'sulu_page.webspace_overview',
        path: '/webspace/:webspace/:locale',
        view: 'sulu_page.webspace_overview',
        attributeDefaults: {
            webspace: 'example',
            locale: 'en',
            utf: true,
        },
    },
]);

router.addUpdateAttributesHooks((route) => ({
    route: route.name,
    locale: 'de',
}));

// navigates to #/webspace/sulu_io/de?utf=true&test=true
router.navigate('sulu_page.webspace_overview', {webspace: 'sulu_io', test: true})
```
