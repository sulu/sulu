The `react` package contains a few functions, which abstract some common tasks, we need quite frequently.

### buildHocDisplayName

This function takes the name of the High-order component, and the component which is decorated by it. Based on this it
returns the name for the High-order component.

```javascript static
class SomeComponent extends React.PureComponent {};

buildHocDisplayName('HighOrderComponent', SomeComponent); // returns HighOrderComponent(SomeComponent)
```
