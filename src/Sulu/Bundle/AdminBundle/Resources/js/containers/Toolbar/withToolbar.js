// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import {buildHocDisplayName} from '../../services/react';
import type {ToolbarConfig} from './types';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';

export default function withToolbar<P, C: Class<Component<P>>>(
    Component: C,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: string = DEFAULT_STORE_KEY
): C {
    const WithToolbarComponent = class extends Component {
        toolbarDisposer: Function;

        componentDidMount() {
            if (super.componentDidMount) {
                super.componentDidMount();
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

    // $FlowFixMe
    return WithToolbarComponent;
}
