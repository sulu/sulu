// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import Router, {getViewKeyFromRoute} from '../../services/Router';
import type {Route} from '../../services/Router';
import viewRegistry from './registries/viewRegistry';
import type {View} from './types';

type Props = {
    router: Router,
};

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

@observer
class ViewRenderer extends React.Component<Props> {
    componentDidMount() {
        const {router} = this.props;

        router.addUpdateRouteHook((newRoute, newAttributes) => {
            const {attributes: oldAttributes, route: oldRoute} = router;
            if (getViewKeyFromRoute(newRoute, newAttributes) !== getViewKeyFromRoute(oldRoute, oldAttributes)) {
                router.clearBindings();
            }

            return true;
        }, UPDATE_ROUTE_HOOK_PRIORITY);
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
