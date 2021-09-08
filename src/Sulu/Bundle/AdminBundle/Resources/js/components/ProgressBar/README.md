A simple progress bar component used to indicate the progress of multiple tasks or processes.

```javascript
<ProgressBar
    value={50}
    max={100}
/>
```

The style can be customized.
Beside the default `progress` skin, it supports a `success` skin,

```javascript
<ProgressBar
    value={100}
    max={100}
    skin="success"
/>
```

an `error` skin

```javascript
<ProgressBar
    value={25}
    max={100}
    skin="error"
/>
```

and a `warning` skin.

```javascript
<ProgressBar
    value={75}
    max={100}
    skin="warning"
/>
```
