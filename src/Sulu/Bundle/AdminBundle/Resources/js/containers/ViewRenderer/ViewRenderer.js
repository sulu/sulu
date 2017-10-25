// @flow
import React from 'react';
import type {Element} from 'react';
import Router from '../../services/Router';
import type {Route} from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';

type Props = {
    router: Router,
};

export default class ViewRenderer extends React.PureComponent<Props> {
    renderView(route: Route, child: Element<*> | null = null) {
        const {router} = this.props;
        const {view} = route;

        const View = viewRegistry.get(view);
        if (!View) {
            throw new Error('View "' + view + '" has not been found');
        }

        const element = (
            <View router={router} route={route}>
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
