The ResourceLocator component can be used to get a URL from user input in two modes.

In the `full` mode the user can change the entire URL except for the leading slash:

```javascript
const [value, setValue] = React.useState('/parent');

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value}</div>
    <ResourceLocator onChange={setValue} value={value} mode="full"/>
</div>
```

In the `leaf` mode the user is only capable of editing the part after the last slash:

```javascript
const [value, setValue] = React.useState('/parent/child');

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value}</div>
    <ResourceLocator onChange={setValue} value={value} mode="leaf"/>
</div>
```

The ResourceLocator also calls its `onBlur` callback when the input loses focus.

```javascript
const [value, setValue] = React.useState('/parent');

<ResourceLocator
    onBlur={() => alert('The ResourceLocator lost its focus')}
    onChange={setValue}
    value={value}
    mode="full"
/>
```
