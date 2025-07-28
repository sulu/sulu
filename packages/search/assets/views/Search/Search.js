// @flow
import React from 'react';
import {withToolbar} from 'sulu-admin-bundle/containers';
import SearchContainer from '../../containers/Search';
import type {ViewProps} from 'sulu-admin-bundle/containers';

class Search extends React.Component<ViewProps> {
    render() {
        const {router} = this.props;

        return (
            <SearchContainer router={router} />
        );
    }
}

export default withToolbar(Search, function() {
    return {};
});
