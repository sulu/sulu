// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import {buildHocDisplayName} from '../../services/react';
import type {ToolbarConfig} from './types';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';

export default function withToolbar(
    Component: Class<Component<*, *>>,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: string = DEFAULT_STORE_KEY
) {
    const WithToolbarComponent = class extends Component {
        toolbarDisposer: Function;

        constructor(props: *) {
            super(props);

            if (super.hasOwnProperty('toolbarDisposer')) {
                throw new Error('Component passed to withToolbar cannot declare a property called "toolbarDisposer".');
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
    };

    WithToolbarComponent.displayName = buildHocDisplayName('withToolbar', Component);

    return WithToolbarComponent;
}
