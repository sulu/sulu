// @flow
import React from 'react';
import Router from '../../services/Router';
import viewStore from './stores/ViewStore';

type Props = {
    name: string,
    router: Router,
};

export default class ViewRenderer extends React.PureComponent<Props> {
    render() {
        const {name, router} = this.props;
        const view = viewStore.get(name);
        if (!view) {
            throw new Error('View "' + name + '" has not been found');
        }

        return React.createElement(view, {router});
    }
}
