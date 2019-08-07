// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import {sidebarStore} from '../Sidebar';
import Router, {getViewKeyFromRoute} from '../../services/Router';
import type {Route} from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';
import type {View} from './types';

type Props = {
    router: Router,
};

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

@observer
class ViewRenderer extends React.Component<Props> {
    componentDidUpdate() {
        this.clearSidebarConfig();
    }

    componentDidMount() {
        const {router} = this.props;

        this.clearSidebarConfig();

        router.addUpdateRouteHook((newRoute, newAttributes) => {
            const {attributes: oldAttributes, route: oldRoute} = router;
            if (getViewKeyFromRoute(newRoute, newAttributes) !== getViewKeyFromRoute(oldRoute, oldAttributes)) {
                router.clearBindings();
            }

            return true;
        }, UPDATE_ROUTE_HOOK_PRIORITY);
    }

    clearSidebarConfig() {
        if (!this.hasSidebar()) {
            sidebarStore.clearConfig();
        }
    }

    hasSidebar(route: ?Route): boolean {
        route = route ? route : this.props.router.route;
        const View = this.getView(route);

        // $FlowFixMe
        if (View.hasSidebar) {
            return true;
        }

        if (route.parent) {
            if (this.hasSidebar(route.parent)) {
                return true;
            }
        }

        // no sidebar found
        return false;
    }

    getView = (route: Route): View => {
        const View = viewRegistry.get(route.view);

        if (!View) {
            throw new Error('View "' + route.view + '" has not been found');
        }

        return View;
    };

    renderView(route: Route, child: Element<*> | null = null) {
        const {router} = this.props;
        const View = this.getView(route);

        const element = (
            <View key={getViewKeyFromRoute(route, router.attributes)} route={route} router={router}>
                {(props) => child ? React.cloneElement(child, props) : null}
            </View>
        );

        if (!route.parent) {
            return element;
        }

        return this.renderView(route.parent, element);
    }

    render() {
        return this.renderView(this.props.router.route);
    }
}

export default ViewRenderer;
