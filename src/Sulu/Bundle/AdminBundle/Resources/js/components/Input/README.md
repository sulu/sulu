The input component can be used to get input from the user in the same way as with the native browser input.

```
const onChange = (value) => {console.log(value)};
<Input onChange={onChange} />
```

Beneath attributes known from the native input, it provides properties to style the the input.

```
const onChange = (value) => {/* do something */};
<Input icon="key" type="password" placeholder="Password" onChange={onChange} />
```
