// @flow
import React from 'react';
import {getView} from '../../services/ViewRegistry';

export default class ViewRenderer extends React.Component {
    props: {
        name: string,
        parameters?: Object,
    };

    render() {
        return React.createElement(getView(this.props.name), this.props.parameters);
    }
}
