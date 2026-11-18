// @flow
import {autorun} from 'mobx';
import log from 'loglevel';
import {getViewKeyFromRoute} from '../../services/Router';
import {buildHocDisplayName} from '../../utils/react';
import viewRendererContext from '../ViewRenderer/viewRendererContext';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/toolbarStorePool';
import type {ViewProps} from '../index';
import type {ToolbarConfig} from './types';
import type {Component} from 'react';

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

export default function withToolbar<P: ViewProps, C: Class<Component<P>>>(
    Component: C,
    toolbar: () => ToolbarConfig,
    toolbarStoreKey: ?string
): C {
    const WithToolbarComponent = class extends Component {
        static contextType = viewRendererContext;

        updateRouteHookDisposer: () => void;
        withToolbarStoreKey: string;

        componentDidMount() {
            if (super.componentDidMount) {
                super.componentDidMount();
            }

            const {router} = this.props;
            const storeKey = toolbarStoreKey || this.context.toolbarStoreKey || DEFAULT_STORE_KEY;
            this.withToolbarStoreKey = storeKey;

            const toolbarDisposer = autorun(() => {
                const toolbarConfig = toolbar.call(this);
                toolbarStorePool.setToolbarConfig(storeKey, toolbarConfig);
                log.info(
                    (WithToolbarComponent.displayName || '') + ' configured toolbar "' + storeKey + '"',
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

            // the container owning a store other than the default one may already have destroyed it
            if (toolbarStorePool.hasStore(this.withToolbarStoreKey)) {
                toolbarStorePool.setToolbarConfig(this.withToolbarStoreKey, {});
            }
        }
    };

    WithToolbarComponent.displayName = buildHocDisplayName('withToolbar', Component);

    // $FlowFixMe
    return WithToolbarComponent;
}
