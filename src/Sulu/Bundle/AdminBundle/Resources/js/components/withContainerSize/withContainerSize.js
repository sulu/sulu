// @flow
import {action, observable} from 'mobx';
import type {ComponentType, ElementRef} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import {buildHocDisplayName} from '../../services/react';
import {afterElementsRendered} from '../../utils/DOM';
import styles from './withContainerSize.scss';
import type {WithContainerSizeElement} from './types';

export default function withContainerSize(Component: ComponentType<*>, containerClass: string = styles.container) {
    @observer
    class WithContainerSizeComponent extends React.Component<*> {
        component: WithContainerSizeElement;

        container: ElementRef<'div'>;

        @observable containerWidth: number = 0;

        @observable containerHeight: number = 0;

        componentDidMount() {
            window.addEventListener('resize', this.handleWindowResize);

            if (typeof this.component.containerDidMount === 'function') {
                afterElementsRendered(this.component.containerDidMount);
            }
        }

        componentWillUnmount() {
            window.removeEventListener('resize', this.handleWindowResize);
        }

        readContainerDimensions = (container: ?ElementRef<'div'>) => {
            afterElementsRendered(action(() => {
                if (!container) {
                    return;
                }

                this.container = container;
                this.containerWidth = container.clientWidth;
                this.containerHeight = container.clientHeight;
            }));
        };

        setComponent = (component: WithContainerSizeElement) => {
            this.component = component;
        };

        handleWindowResize = () => this.readContainerDimensions(this.container);

        render() {
            const props = {
                ...this.props,
                containerWidth: this.containerWidth,
                containerHeight: this.containerHeight,
                ref: this.setComponent,
            };

            return (
                <div className={containerClass} ref={this.readContainerDimensions}>
                    <Component {...props} />
                </div>
            );
        }
    }

    WithContainerSizeComponent.displayName = buildHocDisplayName('withContainerSize', Component);

    return WithContainerSizeComponent;
}
