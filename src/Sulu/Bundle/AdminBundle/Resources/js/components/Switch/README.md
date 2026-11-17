The `Switch` component is a general component with which checkboxes, radio buttons or toggler
can be easily created. For the end user it is easier to use one of these directly.

The `type` prop sets the input type (`checkbox` or `radio`), `size` can be `default` or `small`, `icon` renders an
icon next to the label and `disabled` blocks interaction. The `onChange` callback receives `checked` and the `value`.

```javascript
const [checked, setChecked] = React.useState(false);

<div>
    <Switch checked={checked} onChange={setChecked} value="a">Default</Switch>
    <Switch checked={checked} onChange={setChecked} size="small" value="b">Small</Switch>
    <Switch checked={checked} onChange={setChecked} icon="su-plus" value="c">With icon</Switch>
    <Switch checked={true} type="radio" value="d">Radio</Switch>
    <Switch checked={true} disabled={true} value="e">Disabled</Switch>
</div>
```
