// @flow
import React from 'react';
import Router from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';

type Props = {
    router: Router,
};

export default class ViewRenderer extends React.PureComponent<Props> {
    render() {
        const {router} = this.props;
        const views = [];

        let route = router.route;
        while (route) {
            views.push(route.view);
            route = route.parent;
        }

        let NestedView = null;
        for (const viewName of views) {
            const View = viewRegistry.get(viewName);
            if (!View) {
                throw new Error('View "' + viewName + '" has not been found');
            }

            NestedView = <View router={router}>{NestedView}</View>;
        }

        return NestedView;
    }
}
