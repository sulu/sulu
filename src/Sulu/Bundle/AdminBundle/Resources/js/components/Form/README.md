The `Form` component allows to render fields in a structured form with labels, errors and so on. The form elements can
also be further structured by using `Section`s.

Each field can also have one of multiple types. The `onTypeChange` callback is then called if the type is changed using
the dropdown right next to the label of the field.

```javascript
const types = [
    {label: 'Work', value: 'work'},
    {label: 'Private', value: 'private'},
];

initialState = {
    type: 'work',
};

const handleTypeChange = (type) => {
    setState({type});
};

<Form>
    <Form.Section label="Section 1" size={3}>
        <Form.Field label="Author" onTypeChange={handleTypeChange} types={types} type={state.type}>
            <input type="text" style={{width: '100%'}} />
        </Form.Field>
    </Form.Section>
    <Form.Section label="Section 2" size={9}>
        <Form.Field description="This is the title" label="Title" required={true} size={3}>
            <input type="text" style={{width: '100%'}} />
        </Form.Field>
        <Form.Field description="This is the second title" error="Error!" label="Second title" required={true} size={9}>
            <input type="text" style={{width: '100%'}} />
        </Form.Field>
        <Form.Field description="Article" label="Article">
            <input type="text" style={{width: '100%'}} />
        </Form.Field>
    </Form.Section>
    <Form.Field label="Value">
        <input type="text" style={{width: '100%'}} />
    </Form.Field>
</Form>
```
