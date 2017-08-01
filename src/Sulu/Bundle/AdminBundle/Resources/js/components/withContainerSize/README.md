This is a higher order component which decorates the child component with the width and the height
of the container. `withContainerSize` renders a container around the child component and passes the
width and height of this container to the child via the properties.

```javascript
class Component extends React.PureComponent {
    render = () => <p>{this.props.containerWidth} - {this.props.containerHeight}</p>;
}
const WithSizeComponent = withContainerSize(Component);
```

It has to be noted, that the `containerWidth` as well as the `containerHeight` properties are `0`
at the beginning, as the size of the container can be only determined after everything has rendered.
For that matter the higher-order component calls the `containerDidMount` method right after the mentioned
properties are correctly set.

```javascript
class Component extends React.PureComponent {
    containerDidMount() {
        // container has been mounted and
        // has width this.props.containerWidth and height this.props.containerHeight
    }    
    render = () => <p>Component</p>;
}
const WithSizeComponent = withContainerSize(Component);
```

The default behaviour of the wrapping container is to take the maximum available space.
This can be changed by passing a custom class to the container through the second function parameter.

```javascript
const WithSizeComponent = withContainerSize(Component, 'custom-css-class');
```
