// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStructureStrategy, InfiniteLoadingStrategy} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'su-pen';

@observer
class MediaCardOverviewAdapter extends AbstractAdapter {
    static LoadingStrategy = InfiniteLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-th-large';

    render() {
        return (
            <MediaCardAdapter
                {...this.props}
                icon={EDIT_ICON}
            />
        );
    }
}

export default MediaCardOverviewAdapter;
