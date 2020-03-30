The `Backdrop` component serves as a simple solution to create a backdrop for overlays.

```javascript
<div style={{height: '200px', position: 'relative'}}>
    <Backdrop fixed={false} />
</div>
```

This time the `visible` property is set to false, therefore the backdrop is invisible.

```javascript
<div style={{height: '200px', position: 'relative'}}>
    <Backdrop fixed={false} visible={false} />
</div>
```

The `Backdrop` also accepts an `onClick` handler.

```javascript
<div style={{height: '200px', position: 'relative'}}>
    <Backdrop onClick={() => alert('You clicked the backdrop!')} fixed={false} />
</div>
```
