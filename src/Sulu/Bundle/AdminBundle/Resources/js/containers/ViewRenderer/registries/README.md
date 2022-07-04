The view registry allows to configure different views.

```js
const CustomView = <div>Custom View </div>;

viewRegistry.set('customView', CustomView);
```

An optional ViewConfig parameters allow to define behaviour of the view:

```js
const CustomView = <div>Custom View </div>;

viewRegistry.set('customView', CustomView, { rootSpaceless: true });
```

| Name          | Type    | DefaultValue |
|---------------|---------|--------------|
| rootSpaceless | boolean | false        |
