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
    return {
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
