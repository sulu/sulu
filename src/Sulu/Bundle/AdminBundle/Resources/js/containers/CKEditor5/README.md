This component uses the [CKEditor 5](https://ckeditor.com/ckeditor-5/) to display a text editor. Our component offers a
`value` prop to set the value. There is also an `onChange` callback called when a value changes and a `onBlur` callback
which is called when the editor loses the focus.

```javascript
initialState = {
    value: '',
}

const handleChange = (newValue) => setState({value: newValue});
const handleBlur = () => alert('Text editing finished!');

<div>
    <CKEditor5 onBlur={this.handleBlur} onChange={handleChange} />

    <p>
        Output: <pre>{state.value}</pre>
    </p>
</div>
```
