// @flow
import {autorun} from 'mobx';
import React from 'react';
import type {ComponentType} from 'react';
import type {Item} from './types';
import toolbarStore from './stores/ToolbarStore';

export default function withToolbar(Component: ComponentType<*>, toolbar: () => Array<Item>) {
    const WithToolbarComponent = class extends React.Component<*> {
        disposer: Function;

        setToolbarItems = (component: React$Element<*>) => {
            this.disposer = autorun(() => {
                if (component) {
                    toolbarStore.setItems(toolbar.call(component));
                }
            });
        };

        componentWillUnmount() {
            this.disposer();
        }

        render() {
            return <Component ref={this.setToolbarItems} {...this.props} />;
        }
    };

    const componentName = (typeof Component.displayName === 'string'
        ? Component.displayName
        : (typeof Component.name === 'string'
            ? Component.name
            : ''
        )
    );

    WithToolbarComponent.displayName = `withToolbar(${componentName})`;

    return WithToolbarComponent;
}
