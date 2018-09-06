The `URL` component lets the user choose one of a given set of protocols and type the rest of the URL in an input.

```javascript
initialState = {value: undefined};

const protocols = ['http://', 'https://'];

const onChange = (value) => {
    setState({value});
};

<div>
    <Url onChange={onChange} protocols={protocols} value={state.value} />
    <p>Returned URL: {state.value}</p>
</div>
```

It also automatically detects the protocol of a given URL and sets the value of the dropdown correctly.

```javascript
initialState = {
    value: 'http://www.sulu.io',
};

const protocols = ['http://', 'https://'];

const onChange = (value) => {
    setState({value});
};

<div>
    <Url onChange={onChange} protocols={protocols} value={state.value} />
    <p>Returned URL: {state.value}</p>
</div>
```

Finally it validates the entered URL. That also works when it is passed in initially:

```javascript
initialState = {
    value: 'http://www.su lu.at',
};

const protocols = ['http://', 'https://'];

const onChange = (value) => {
    setState({value});
};

<div>
    <Url onChange={onChange} protocols={protocols} value={state.value} />
    <p>Returned URL: {state.value}</p>
</div>
```
