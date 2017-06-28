// @flow
import React from 'react';
import {toolbarStore} from '../../containers/Toolbar';

export default class Form extends React.PureComponent {
    componentWillMount() {
        toolbarStore.setItems([
            {
                title: 'Save',
            },
        ]);
    }

    render() {
        return (
            <h1>Form</h1>
        );
    }
}
