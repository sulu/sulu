The `Form` component allows to render fields in a structured form with labels, errors and so on. The form elements can
also be further structured by using `Section`s.

```javascript
const Form = require('./Form').default;

<Form>
    <Form.Section label="Section 1" size={3}>
        <Form.Field label="Author">
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
