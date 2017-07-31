// @flow
import React from 'react';

export default function containerSizeAware(Component: ReactClass<*>) {
    class ContainerSizeAwareComponent extends React.Component {
        component: *;

        componentDidMount() {
            this.component.containerDidMount();
            if (this.props.mountSpy) {
                Promise.resolve().then(this.props.mountSpy);
            }
        }

        setComponent = (c: *) => this.component = c;

        render() {
            const props = {
                ...this.props,
                containerWidth: 2000,
                containerHeight: 1000,
                ref: this.setComponent,
            };

            return <Component {...props} />;
        }
    }

    return ContainerSizeAwareComponent;
}
