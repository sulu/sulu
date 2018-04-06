// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import {buildHocDisplayName} from '../../services/react';
import type {SidebarConfig} from './types';
import sidebarStore from './stores/SidebarStore';

export default function withSidebar(
    Component: Class<Component<*, *>>,
    sidebar: () => SidebarConfig
) {
    const WithSidebarComponent = class extends Component {
        static hasSidebar = true;

        sidebarDisposer: Function;

        componentWillMount() {
            if (super.hasOwnProperty('sidebarDisposer')) {
                throw new Error('Component passed to withSidebar cannot declare a property called "sidebarDisposer".');
            }

            if (super.componentWillMount) {
                super.componentWillMount();
            }

            this.sidebarDisposer = autorun(() => {
                sidebarStore.setConfig(sidebar.call(this));
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

    return WithSidebarComponent;
}
