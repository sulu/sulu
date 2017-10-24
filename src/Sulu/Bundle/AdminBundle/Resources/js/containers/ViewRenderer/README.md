The `ViewRenderer` is a simple component, which loads the registered view from its `ViewRegistry`, and renders it as a
React component and passes it the [`Router` service](#router), which is passed in as a property.

Registering a component in the `ViewRegistry` and rendering it using the `ViewRenderer` is shown in the following
example:

```
const viewRegistry = require('./registries/ViewRegistry').default;
viewRegistry.clear(); // Just to make sure the ViewRegistry is empty, not needed in a real world application

const Component = ({router}) => (
    <div>
        <h1>View component</h1>
        <p>The next paragraph will show an attribute from the mocked Router.</p>
        <p>{router.attributes.content}</p>
    </div>
);
viewRegistry.add('view', Component);

// instead of this mocked Router you would usually use a real one
const route = {
    view: 'view',
};
const router = {
    attributes: {
        content: 'Some trivial content!',
    },
    route: route,
};

<ViewRenderer route={route} router={router} />
```

The `ViewRegistry` can also handle the parent and children relation ships built on top of routes. It will nest the
route's views in each other, whereby the parent route's view gets the children route's view via the `children`
property. The `children` property is a function, which returns the corresponding view. It takes an object as only
argument, which will be merged with the passed `route` and `router` props from the view.

```js
const viewRegistry = require('./registries/ViewRegistry').default;
viewRegistry.clear();

const Parent = ({route, children}) => (
    <div>
        <h1>{route.name}</h1>
        {children({value: 'bla'})}
    </div>
);

const Child = ({route, value}) => (
    <div>
        <h2>{route.name}</h2>
        <p>{value}</p>
    </div>
);

viewRegistry.add('parent', Parent);
viewRegistry.add('child', Child);

const parentRoute = {
    name: 'Parent',
    view: 'parent',
};

const childRoute = {
    name: 'Child',
    parent: parentRoute,
    view: 'child',
};

const router = {
    route: childRoute,
};

<ViewRenderer router={router} />
```
