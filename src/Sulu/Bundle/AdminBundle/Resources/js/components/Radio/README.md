Radio buttons keep no internal state and have to be managed from the outside, like shown in the
following example:

```javascript
const [value, setValue] = React.useState('1');

<div>
    <Radio checked={value === '1'} onChange={() => setValue('1')}>Radio 1</Radio>
    <Radio checked={value === '2'} onChange={() => setValue('2')}>Radio 2</Radio>
    <Radio checked={value === '3'} onChange={() => setValue('3')}>Radio 3</Radio>
</div>
```

Radio buttons come with two different styles which can be set through the `skin` prop. The default skin is "dark".
The "light" style looks like as in the following example:

```javascript
const [value, setValue] = React.useState('1');

<div style={{ backgroundColor: '#888', padding: '10px' }}>
    <Radio skin="light" checked={value === '1'} onChange={() => setValue('1')}>Radio 1</Radio>
    <Radio skin="light" checked={value === '2'} onChange={() => setValue('2')}>Radio 2</Radio>
    <Radio skin="light" checked={value === '3'} onChange={() => setValue('3')}>Radio 3</Radio>
</div>
```

In most cases the state management of the radio buttons will be the same.
For that matter the `RadioGroup` component makes the use of the radio buttons more convenient.

```javascript
const RadioGroup = require('./RadioGroup').default;

const [value, setValue] = React.useState('1');

<RadioGroup value={value} onChange={setValue}>
    <Radio value="1">Radio 1</Radio>
    <Radio value="2">Radio 2</Radio>
    <Radio value="3">Radio 3</Radio>
</RadioGroup>
```

The `disabled` prop on a `Radio` or on the whole `RadioGroup` blocks interaction. The `name` prop is passed to the input.

```javascript
const RadioGroup = require('./RadioGroup').default;

<div>
    <Radio checked={true} disabled={true}>Disabled radio</Radio>
    <RadioGroup disabled={true} value="1">
        <Radio value="1">Disabled group 1</Radio>
        <Radio value="2">Disabled group 2</Radio>
    </RadioGroup>
</div>
```
