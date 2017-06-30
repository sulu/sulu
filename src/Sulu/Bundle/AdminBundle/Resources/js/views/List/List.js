// @flow
import React from 'react';
import {toolbarStore} from '../../containers/Toolbar';

export default class List extends React.PureComponent {
    componentWillMount() {
        toolbarStore.setItems([
            {
                title: 'Add',
                icon: 'plus-circle',
                onClick: () => {
                    console.log('Add clicked');
                },
            },
            {
                title: 'Delete',
                icon: 'trash-o',
                onClick: () => {
                    console.log('Delete clicked');
                },
            },
        ]);
    }

    render() {
        return (
            <h1>List</h1>
        );
    }
}
