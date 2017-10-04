The `Form` component allows to show a form based on a schema. This schema defines which fields the form should display.
In addition to that the component receives a `FormStore` and an `onSubmit` callback. The `FormStore` holds all the
current data displayed in the form. The `Form` component will update this store everytime something changes.

The `onSubmit` callback will be executed when the `Form` is submitted. The data of the form can then be taken from the
passed `FormStore`.

```javascript static
const schema = {
    title: {},
    description: {},
};
const store = new FormStore('snippets');
store.changeSchema(schema);

function handleSubmit() {
    console.log(store.data);
}

<Form schema={schema} store={store} onSubmit={handleSubmit} />
```
