// @flow
import {autorun} from 'mobx';
import type {Component} from 'react';
import log from 'loglevel';
import {getViewKeyFromRoute} from '../../services/Router';
import {buildHocDisplayName} from '../../utils/react';
import type {ViewProps} from '../index';
import type {ToolbarConfig} from './types';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/toolbarStorePool';

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

export default function withToolbar<P: ViewProps, C: Class<Component<P>>>(
    Component: C,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: string = DEFAULT_STORE_KEY
): C {
    const WithToolbarComponent = class extends Component {
        updateRouteHookDisposer: () => void;

        componentDidMount() {
            if (super.componentDidMount) {
                super.componentDidMount();
            }

            const {router} = this.props;

            const toolbarDisposer = autorun(() => {
                const toolbarConfig = toolbar.call(this);
                toolbarStorePool.setToolbarConfig(toolbarStoreKey, toolbarConfig);
                log.info(
                    (WithToolbarComponent.displayName || '') + ' configured toolbar "' + toolbarStoreKey + '"',
                    toolbarConfig
                );
            });

            this.updateRouteHookDisposer = router.addUpdateRouteHook((newRoute, newAttributes) => {
                const {attributes: oldAttributes, route: oldRoute} = router;
                if (getViewKeyFromRoute(newRoute, newAttributes) !== getViewKeyFromRoute(oldRoute, oldAttributes)) {
                    toolbarDisposer();
                }

                return true;
            }, UPDATE_ROUTE_HOOK_PRIORITY);
        }

        componentWillUnmount() {
            if (super.componentWillUnmount) {
                super.componentWillUnmount();
            }

            this.updateRouteHookDisposer();

            toolbarStorePool.setToolbarConfig(toolbarStoreKey, {});
        }
    };

    WithToolbarComponent.displayName = buildHocDisplayName('withToolbar', Component);

    // $FlowFixMe
    return WithToolbarComponent;
}
