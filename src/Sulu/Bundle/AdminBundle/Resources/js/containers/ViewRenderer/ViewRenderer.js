// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import Router from '../../services/Router';
import type {Route} from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';

type Props = {
    router: Router,
};

@observer
export default class ViewRenderer extends React.Component<Props> {
    getKey = (route: Route, router: Router) => {
        if (!route.rerenderAttributes) {
            return undefined;
        }

        const rerenderAttributeValues = [];

        route.rerenderAttributes.forEach((rerenderAttribute) => {
            if (router.attributes && router.attributes.hasOwnProperty(rerenderAttribute)) {
                rerenderAttributeValues.push(router.attributes[rerenderAttribute]);
            }
        });

        return rerenderAttributeValues.join('__');
    };

    renderView(route: Route, child: Element<*> | null = null) {
        const {router} = this.props;
        const {view} = route;

        const View = viewRegistry.get(view);
        if (!View) {
            throw new Error('View "' + view + '" has not been found');
        }

        const element = (
            <View router={router} route={route} key={this.getKey(route, router)}>
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
