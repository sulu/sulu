// @flow
import React from 'react';
import {toolbarStore} from '../../containers/Toolbar';

export default class List extends React.PureComponent {
    componentWillMount() {
        toolbarStore.setItems([]);
    }

    render() {
        return (
            <h1>List</h1>
        );
    }
}
