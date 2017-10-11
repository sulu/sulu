// @flow
import React from 'react';
import Router from '../../services/Router';
import viewRegistry from './registries/ViewRegistry';

type Props = {
    name: string,
    router: Router,
};

export default class ViewRenderer extends React.PureComponent<Props> {
    render() {
        const {name, router} = this.props;
        const View = viewRegistry.get(name);
        if (!View) {
            throw new Error('View "' + name + '" has not been found');
        }

        return (
            <View router={router} />
        );
    }
}
