// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import {sidebarStore} from '../Sidebar';
import Router from '../../services/Router';
import type {AttributeMap, Route} from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';
import type {View} from './types';

type Props = {
    router: Router,
};

const UPDATE_ROUTE_HOOK_PRIORITY = -1024;

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
            if (this.getKey(newRoute, newAttributes) !== this.getKey(oldRoute, oldAttributes)) {
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

    getKey = (route: ?Route, attributes: ?AttributeMap) => {
        if (!route) {
            return null;
        }

        const rerenderAttributeValues = [];

        if (route.rerenderAttributes) {
            route.rerenderAttributes.forEach((rerenderAttribute) => {
                if (attributes && attributes.hasOwnProperty(rerenderAttribute)) {
                    rerenderAttributeValues.push(attributes[rerenderAttribute]);
                }
            });
        }

        return route.name + (rerenderAttributeValues.length > 0 ? '-' + rerenderAttributeValues.join('__') : '');
    };

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
            <View key={this.getKey(route, router.attributes)} route={route} router={router}>
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
