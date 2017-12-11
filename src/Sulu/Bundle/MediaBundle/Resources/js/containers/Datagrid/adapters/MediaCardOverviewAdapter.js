// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStrategy, InfiniteScrollingStrategy} from 'sulu-admin-bundle/containers';
import type {LoadingStrategyInterface, StructureStrategyInterface} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'pencil';

@observer
export default class MediaCardOverviewAdapter extends AbstractAdapter {
    static getLoadingStrategy(): LoadingStrategyInterface {
        return new InfiniteScrollingStrategy();
    }

    static getStructureStrategy(): StructureStrategyInterface {
        return new FlatStrategy();
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
