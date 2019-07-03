// @flow
import React from 'react';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import SearchContainer from '../../containers/Search';

class Search extends React.Component<ViewProps> {
    render() {
        return (
            <SearchContainer />
        );
    }
}

export default withToolbar(Search, function() {
    return {};
});
