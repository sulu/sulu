// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {observer} from 'mobx-react';
import styles from './withContainerSize.scss';

export default function withContainerSize(Component: ReactClass<*>, containerClass: string = styles.container) {
    @observer
    class WithContainerSizeComponent extends React.Component {
        component: Component;
        container: HTMLElement;
        @observable containerWidth: number = 0;
        @observable containerHeight: number = 0;

        componentDidMount() {
            window.addEventListener('resize', this.handleWindowResize);
            if (this.component.containerDidMount) {
                window.requestAnimationFrame(this.component.containerDidMount);
            }
        }

        componentWillUnmount() {
            window.removeEventListener('resize', this.handleWindowResize);
        }

        readContainerDimensions = (container: HTMLElement) => {
            if (!container) {
                return;
            }
            window.requestAnimationFrame(action(() => {
                this.container = container;
                this.containerWidth = container.clientWidth;
                this.containerHeight = container.clientHeight;
            }));
        };

        setComponent = (c: Component) => this.component = c;
        handleWindowResize = () => this.readContainerDimensions(this.container);

        render() {
            const props = {
                ...this.props,
                containerWidth: this.containerWidth,
                containerHeight: this.containerHeight,
                ref: this.setComponent,
            };

            return (
                <div ref={this.readContainerDimensions} className={containerClass}>
                    <Component {...props} />
                </div>
            );
        }
    }

    WithContainerSizeComponent.displayName = `withContainerSize(${Component.displayName || Component.name})`;

    return WithContainerSizeComponent;
}
