// @flow
import React from 'react';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';

class List extends React.PureComponent<*> {
    render() {
        return (
            <h1>List</h1>
        );
    }
}

export default withToolbar(List, function() {
    return [
        {
            title: translate('sulu_admin.add'),
            icon: 'plus-circle',
        },
        {
            title: translate('sulu_admin.delete'),
            icon: 'trash-o',
        },
    ];
});
