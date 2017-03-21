// @flow
import React from 'react';

const map = {};

export function addView(name: string, view: ReactClass<*>) {
    map[name] = view;
}

type Props = {
    name: string,
    parameters: Object,
};

export class ViewRenderer extends React.Component {
    props: Props;

    render() {
        return React.createElement(map[this.props.name], this.props.parameters);
    }
}
