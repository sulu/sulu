// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStructureStrategy, InfiniteLoadingStrategy} from 'sulu-admin-bundle/containers';
import type {LoadingStrategyInterface} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'su-pen';

@observer
class MediaCardOverviewAdapter extends AbstractAdapter {
    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-th-large';

    // eslint-disable-next-line no-unused-vars
    static getLoadingStrategy(options: Object = {}): Class<LoadingStrategyInterface> {
        return InfiniteLoadingStrategy;
    }

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
