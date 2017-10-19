// @flow
import {autorun} from 'mobx';
import type {ComponentType} from 'react';
import React from 'react';
import {buildHocDisplayName} from '../../services/react';
import type {ToolbarConfig} from './types';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';

export default function withToolbar(
    Component: ComponentType<*>,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: string = DEFAULT_STORE_KEY
) {
    const WithToolbarComponent = class extends Component {
        toolbarDisposer: Function;

        componentWillMount() {
            if (super.componentWillMount) {
                super.componentWillMount();
            }

            this.toolbarDisposer = autorun(() => {
                toolbarStorePool.setToolbarConfig(toolbarStoreKey, toolbar.call(this));
            });
        }

        componentWillUnmount() {
            if (super.componentWillUnmount) {
                super.componentWillUnmount();
            }

            this.toolbarDisposer();
        }

        render() {
            return super.render();
        }
    };

    WithToolbarComponent.displayName = buildHocDisplayName('withToolbar', Component);

    return WithToolbarComponent;
}
