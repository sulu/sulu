The `DOM` package contains a few functions, which allow for an easier interaction with the DOM.

### afterElementsRendered

This function takes a callback, which is executed as soon as all the elements in the DOM have actually been rendered.

```javascript static
afterElementsRendered(() => {
    alert('All elements have rendered!');
})
```
