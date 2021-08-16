A simple progress bar component used to indicate the progress of multiple tasks or processes.

```javascript
<ProgressBar
    value={50}
    max={100}
/>
```

The style can be customized.
Beside the default `progress` style, it supports a `success` style,

```javascript
<ProgressBar
    value={100}
    max={100}
    type="success"
/>
```

an `error` style

```javascript
<ProgressBar
    value={25}
    max={100}
    type="error"
/>
```

and a `warning` style.

```javascript
<ProgressBar
    value={75}
    max={100}
    type="warning"
/>
```
