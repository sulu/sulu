// @flow
import React from 'react';
import {withToolbar} from '../../containers/Toolbar';
import translator from '../../services/Translator';

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
            title: translator.translate('sulu_admin.add'),
            icon: 'plus-circle',
        },
        {
            title: translator.translate('sulu_admin.delete'),
            icon: 'trash-o',
        },
    ];
});
