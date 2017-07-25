// @flow
import type {Item} from './types';
import React from 'react';
import {autorun} from 'mobx';
import toolbarStore from './stores/ToolbarStore';

export default function withToolbar(Component: ReactClass<*>, toolbar: () => Array<Item>) {
    const WithToolbarComponent = class extends React.Component {
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

    WithToolbarComponent.displayName = `withToolbar(${Component.displayName || Component.name})`;

    return WithToolbarComponent;
}
