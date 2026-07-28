The `Snackbar` component is used to display information that is moving in from the top of the screen using an animation.

```javascript
<Snackbar message="Some error is shown here" type="error" />
```

Besides errors it can also show `warning`, `success` and `info` messages:

```javascript
<div style={{display: 'flex', flexDirection: 'column', gap: '10px'}}>
    <Snackbar message="Some warning is shown here" type="warning" />
    
    <Snackbar message="Some information is shown here" type="info" />
    
    <Snackbar message="Some not so bad went wrong" type="success" />
</div>
```

There is also a behaviour `floating` option used for floating snackbar:

```javascript

<div style={{height: '130px', position: 'relative'}}>
    <div style={{position: 'absolute', bottom: '0', left: '0', right: '0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '10px', marginLeft: 'auto', marginRight: 'auto', width: 'fit-content'}}>
        <Snackbar skin="floating" message="Some error is shown here" type="error" />
        
        <Snackbar skin="floating" message="Some warning is shown here" type="warning" />
        
        <Snackbar skin="floating" message="Some information is shown here" type="info" />
        
        <Snackbar skin="floating" message="Some not so bad went wrong" type="success" />
    </div>
</div>
```

This is used as example for block snackbar messages like the following:

```javascript

<div style={{height: '32px', position: 'relative'}}>
    <div style={{position: 'absolute', bottom: '0', left: '0', right: '0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '10px', marginLeft: 'auto', marginRight: 'auto', width: 'fit-content'}}>
        <Snackbar skin="floating" icon="su-copy" message="3 blocks copied to clipboard" type="info" />
    </div>
</div>
```

There are two different callbacks that can be added. One is the `onCloseClick` callback, which makes a close button
appear calling this callback when it is clicked.

```javascript
const closeClickHandler = () => alert('The snackbar should be closed now');

<Snackbar message="Something went wrong" onCloseClick={closeClickHandler} type="error" />
```

The second one is the `onClick` callback, which is called whenever the snackbar is clicked.

```javascript
const clickHandler = () => alert('The snackbar was clicked');

<Snackbar message="Something went wrong" onClick={clickHandler} type="error" />
```

The `title` prop replaces the type name shown in front of the message, and the `action` prop adds a link at the end of
the snackbar. Both are useful to describe a more specific situation than the plain type does.

```javascript
const retryHandler = () => alert('The failed request should be retried now');

<div style={{display: 'flex', flexDirection: 'column', gap: '10px'}}>
    <Snackbar
        action={{label: 'Try Again', onClick: retryHandler}}
        icon="su-exclamation-triangle"
        message="Your request couldn't be completed right now. This is usually a temporary issue."
        title="Something went wrong"
        type="warning"
    />

    <Snackbar
        action={{label: 'Contact Admin', onClick: retryHandler}}
        message="Your AI credits have been used up."
        title="Out of Credits"
        type="error"
    />
</div>
```

The toolbar is also animated when it (dis)appears.

```javascript
const [visible, setVisible] = React.useState(true);

const clickHandler = () => setVisible(!visible);

<div style={{overflow: 'hidden'}}>
    <Snackbar message="Something went wrong" type="error" visible={visible} />
    <button onClick={clickHandler}>Toggle Snackbar</button>
</div>
```
