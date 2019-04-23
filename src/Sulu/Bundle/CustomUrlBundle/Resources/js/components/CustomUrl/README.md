This component takes a base domain as prop and allows to fill its placeholder marked with asterisks with actual values. 
The component shows the placeholders as inputs.

The placeholder can be passed as subdomain.

```javascript
initialState = {
    value: ['sulu-20']
};

const handleChange = (value) => setState({value});

<CustomUrl baseDomain="*.sulu.io" onChange={handleChange} value={state.value} />
```

Or it can be used as the path of the URL.

```javascript
initialState = {
    value: ['sulu-20']
};

const handleChange = (value) => setState({value});

<CustomUrl baseDomain="sulu.io/*" onChange={handleChange} value={state.value} />
```

It can also be a mixture of the previous two variants.

```javascript
initialState = {
    value: ['releases', 'landingpages', 'sulu-20']
};

const handleChange = (value) => setState({value});

<CustomUrl baseDomain="*.*.sulu.io/*" onChange={handleChange} value={state.value} />
```
