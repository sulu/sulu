// @flow
import React from 'react';
import type {ComponentType} from 'react';

export default function withContainerSize(Component: ComponentType<*>) {
    class withContainerSizeComponent extends React.Component<*> {
        component: *;

        componentDidMount() {
            if (this.component.containerDidMount) {
                this.component.containerDidMount();
            }
            if (this.props.mountSpy) {
                Promise.resolve().then(this.props.mountSpy);
            }
        }

        setComponent = (c: *) => this.component = c;

        render() {
            const props = {
                ...this.props,
                containerWidth: 640,
                containerHeight: 360,
                ref: this.setComponent,
            };

            return <Component {...props} />;
        }
    }

    return withContainerSizeComponent;
}
