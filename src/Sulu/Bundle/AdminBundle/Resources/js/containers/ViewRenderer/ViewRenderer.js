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
        const viewConfigs = [];

        let route = router.route;
        while (route) {
            viewConfigs.push({
                view: route.view,
                props: {
                    router: router,
                    route,
                },
            });
            route = route.parent;
        }

        let NestedView = null;
        for (const viewConfig of viewConfigs) {
            const {view, props} = viewConfig;
            const View = viewRegistry.get(view);
            if (!View) {
                throw new Error('View "' + view + '" has not been found');
            }

            NestedView = <View {...props}>{NestedView}</View>;
        }

        return NestedView;
    }
}
