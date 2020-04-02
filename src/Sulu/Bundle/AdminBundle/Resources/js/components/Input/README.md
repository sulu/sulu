The input component can be used to get input from the user in the same way as with the native browser input.

```javascript
const [value, setValue] = React.useState('');

<Input value={value} onChange={setValue} />
```

Beneath attributes known from the native input, it provides properties to style the the input.

```javascript
const [value, setValue] = React.useState('');

<Input icon="fa-key" type="password" placeholder="Password" value={value} onChange={setValue} />
```

It also offers a `headline` prop, which allows to use distinguish more important fields from others.

```javascript
const [value, setValue] = React.useState('');

<Input icon="fa-key" headline={true} value={value} onChange={setValue} />
```

When setting the `valid` prop to `false` it will mark the field as invalid. The following example shows an input field
that needs to contain some text.

```javascript
const [value, setValue] = React.useState('');
const [valid, setValid] = React.useState(false);

const onChange = (newValue) => {
    setValue(newValue);
    setValid(!!newValue);
};

<Input valid={valid} value={value} onChange={onChange} />
```

In addition to that the `onBlur` callback will be executed when `Input` components loses the focus.

```javascript
const [value, setValue] = React.useState('');

<Input value={value} onChange={setValue} onBlur={() => alert('Focus lost!')} />
```

The component also supports limiting the amount of characters typed into it.

```javascript
const [value, setValue] = React.useState('');

<Input value={value} maxCharacters={5} onChange={setValue} />
```

It even supports limiting the amount of segments, which is super useful if a specific number of keywords should be
delimited e.g. by a comma.

```javascript
const [value, setValue] = React.useState('');

<Input value={value} maxSegments={5} segmentDelimiter="," onChange={setValue} />
```
