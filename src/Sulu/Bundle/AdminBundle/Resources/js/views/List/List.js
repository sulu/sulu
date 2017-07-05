// @flow
import React from 'react';
import {withToolbar} from '../../containers/Toolbar';

class List extends React.PureComponent {
    render() {
        return (
            <h1>List</h1>
        );
    }
}

export default withToolbar(List, function() {
    return [
        {
            title: 'Add',
            icon: 'plus-circle',
        },
        {
            title: 'Delete',
            icon: 'trash-o',
        },
    ];
});
