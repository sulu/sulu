// @flow
import {autorun} from 'mobx';
import React from 'react';
import type {ComponentType} from 'react';
import {buildHocDisplayName} from '../../services/react';
import type {ToolbarConfig} from './types';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';

export default function withToolbar(
    Component: ComponentType<*>,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: string = DEFAULT_STORE_KEY
) {
    const WithToolbarComponent = class extends React.Component<*> {
        disposer: Function;

        setToolbarConfig = (component: React$Element<*>) => {
            this.disposer = autorun(() => {
                if (component) {
                    toolbarStorePool.setToolbarConfig(toolbarStoreKey, toolbar.call(component));
                }
            });
        };

        componentWillUnmount() {
            this.disposer();
        }

        render() {
            return <Component ref={this.setToolbarConfig} {...this.props} />;
        }
    };

    WithToolbarComponent.displayName = buildHocDisplayName('withToolbar', Component);

    return WithToolbarComponent;
}
