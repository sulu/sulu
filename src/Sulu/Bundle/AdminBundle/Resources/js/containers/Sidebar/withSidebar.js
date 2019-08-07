// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import {buildHocDisplayName} from '../../utils/react';
import type {SidebarConfig} from './types';
import sidebarStore from './stores/SidebarStore';

export default function withSidebar<P, C: Class<Component<P>>>(
    Component: C,
    sidebar: () => ?SidebarConfig
): C {
    const WithSidebarComponent = class extends Component {
        static hasSidebar = true;

        sidebarDisposer: Function;

        componentDidMount() {
            if (super.componentDidMount) {
                super.componentDidMount();
            }

            if (super.hasOwnProperty('sidebarDisposer')) {
                throw new Error('Component passed to withSidebar cannot declare a property called "sidebarDisposer".');
            }

            this.sidebarDisposer = autorun(() => {
                const sidebarConfig = sidebar.call(this);
                if (!sidebarConfig) {
                    sidebarStore.clearConfig();

                    return;
                }

                sidebarStore.setConfig(sidebarConfig);
            });
        }

        componentWillUnmount() {
            if (super.componentWillUnmount) {
                super.componentWillUnmount();
            }

            this.sidebarDisposer();
        }
    };

    WithSidebarComponent.displayName = buildHocDisplayName('withSidebar', Component);

    // $FlowFixMe
    return WithSidebarComponent;
}
