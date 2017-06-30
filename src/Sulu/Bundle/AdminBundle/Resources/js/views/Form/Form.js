// @flow
import React from 'react';
import {toolbarStore} from '../../containers/Toolbar';

export default class Form extends React.PureComponent {
    componentWillMount() {
        toolbarStore.setItems([
            {
                title: 'Save',
                icon: 'floppy-o',
                onClick: () => {
                    console.log('Save clicked');
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
            <h1>Form</h1>
        );
    }
}
