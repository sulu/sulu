The `Form` component allows to show a form based on a `FormStore`. This store holds the schema, which defines which
fields the form should display.  The `FormStore` also holds all the current data displayed in the form. The `Form`
component will update this store everytime something changes.

In addition that it also takes an `onSubmit` callback, which will be executed when the `Form` is submitted. The data of
the form can then be taken from the passed `FormStore`.

```javascript static
const store = new FormStore('snippets');

function handleSubmit() {
    console.log(store.data);
}

<Form store={store} onSubmit={handleSubmit} />
```
