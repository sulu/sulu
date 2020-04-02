This component takes a base domain as prop and allows to fill its placeholder marked with asterisks with actual values. 
The component shows the placeholders as inputs.

The placeholder can be passed as subdomain.

```javascript
const [value, setValue] = React.useState(['sulu-20']);

const handleChange = (value) => setValue(value);

<CustomUrl baseDomain="*.sulu.io" onChange={handleChange} value={value} />
```

Or it can be used as the path of the URL.

```javascript
const [value, setValue] = React.useState(['sulu-20']);

const handleChange = (value) => setValue(value);

<CustomUrl baseDomain="sulu.io/*" onChange={handleChange} value={value} />
```

It can also be a mixture of the previous two variants.

```javascript
const [value, setValue] = React.useState(['sulu-20']);

const handleChange = (value) => setValue(value);

<CustomUrl baseDomain="*.*.sulu.io/*" onChange={handleChange} value={value} />
```
