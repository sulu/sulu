// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import log from 'loglevel';
import {getViewKeyFromRoute} from '../../services/Router';
import {buildHocDisplayName} from '../../utils/react';
import type {ViewProps} from '../index';
import type {SidebarConfig} from './types';
import sidebarStore from './stores/sidebarStore';

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

export default function withSidebar<P: ViewProps, C: Class<Component<P>>>(
    Component: C,
    sidebar: () => ?SidebarConfig
): C {
    const WithSidebarComponent = class extends Component {
        updateRouteHookDisposer: () => void;

        componentDidMount() {
            if (super.componentDidMount) {
                super.componentDidMount();
            }

            const {router} = this.props;

            const sidebarDisposer = autorun(() => {
                const sidebarConfig = sidebar.call(this);
                if (!sidebarConfig) {
                    sidebarStore.clearConfig();

                    return;
                }

                sidebarStore.setConfig(sidebarConfig);

                log.info((WithSidebarComponent.displayName || '') + ' configured sidebar', sidebarConfig);
            });

            this.updateRouteHookDisposer = router.addUpdateRouteHook((newRoute, newAttributes) => {
                const {attributes: oldAttributes, route: oldRoute} = router;
                if (getViewKeyFromRoute(newRoute, newAttributes) !== getViewKeyFromRoute(oldRoute, oldAttributes)) {
                    sidebarDisposer();
                }

                return true;
            }, UPDATE_ROUTE_HOOK_PRIORITY);
        }

        componentWillUnmount() {
            if (super.componentWillUnmount) {
                super.componentWillUnmount();
            }

            this.updateRouteHookDisposer();
            sidebarStore.clearConfig();
        }
    };

    WithSidebarComponent.displayName = buildHocDisplayName('withSidebar', Component);

    // $FlowFixMe
    return WithSidebarComponent;
}
