// @flow
import React from 'react';
import viewStore from './stores/ViewStore';

export default class ViewRenderer extends React.PureComponent {
    props: {
        name: string,
        parameters?: Object,
    };

    render() {
        return React.createElement(viewStore.get(this.props.name), this.props.parameters);
    }
}
