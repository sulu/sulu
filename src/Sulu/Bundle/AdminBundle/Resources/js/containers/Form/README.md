The `Form` component allows to show a form based on a `FormStore`. This store holds the schema, which defines which
fields the form should display.  The `FormStore` also holds all the current data displayed in the form. The `Form`
component will update this store everytime something changes.

There are two different kind of stores: The `ResourceFormStore` will use a [`ResourceStore`](#resourceStore) to load and
save the data. The other one is the `MemoryFormStore`, which will keep the data for the form in a simple variable.

The component also takes an `onSubmit` callback, which will be executed when the `Form` is submitted. However, if
there is an error in the form when it is trying to be submitted, the component will call the `onError` callback instead.
The data of the form can then be taken from the passed `FormStore`.

```javascript static
const store = new FormStore('snippets');

function handleSubmit() {
    console.log(store.data);
}

<Form store={store} onSubmit={handleSubmit} />
```
