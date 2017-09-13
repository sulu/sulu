// @flow
import React from 'react';
import {withToolbar} from 'sulu-admin-bundle';
import type {ViewProps} from 'sulu-admin-bundle/containers/ViewRenderer/types';

class MediaList extends React.PureComponent<ViewProps> {
    render() {
        return (
            <div>
                <h1>List</h1>
                <a href="#/snippets/123">To the Form</a>
            </div>
        );
    }
}

export default withToolbar(MediaList, function() {
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
