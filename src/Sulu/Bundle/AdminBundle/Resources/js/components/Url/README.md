The `URL` component lets the user choose one of a given set of protocols and type the rest of the URL in an input.

```javascript
const [value, setValue] = React.useState(undefined);

const protocols = ['http://', 'https://'];

<div>
    <Url onChange={setValue} protocols={protocols} value={value} />
    <p>Returned URL: {value}</p>
</div>
```

It also automatically detects the protocol of a given URL and sets the value of the dropdown correctly.

```javascript
const [value, setValue] = React.useState('http://www.sulu.at');

const protocols = ['http://', 'https://'];

<div>
    <Url onChange={setValue} protocols={protocols} value={value} />
    <p>Returned URL: {value}</p>
</div>
```

Finally it validates the entered URL. That also works when it is passed in initially:

```javascript
const [value, setValue] = React.useState('http://www.su lu.at');

const protocols = ['http://', 'https://'];

<div>
    <Url onChange={setValue} protocols={protocols} value={value} />
    <p>Returned URL: {value}</p>
</div>
```
