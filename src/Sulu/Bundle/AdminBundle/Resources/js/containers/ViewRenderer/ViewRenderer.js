// @flow
import React from 'react';
import viewStore from './stores/ViewStore';

type Props = {
    name: string,
    parameters: Object,
};

export default class ViewRenderer extends React.PureComponent<Props> {
    render() {
        const view = viewStore.get(this.props.name);
        if (!view) {
            throw new Error('View "' + this.props.name + '" has not been found');
        }

        return React.createElement(view, this.props.parameters);
    }
}
