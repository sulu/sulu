// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStructureStrategy, InfiniteLoadingStrategy} from 'sulu-admin-bundle/containers';
import type {LoadingStrategyInterface} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const SELECT_ICON = 'su-check';

@observer
class MediaCardSelectionAdapter extends AbstractAdapter {
    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-th-large';

    // eslint-disable-next-line no-unused-vars
    static getLoadingStrategy(options: Object = {}): Class<LoadingStrategyInterface> {
        return InfiniteLoadingStrategy;
    }

    handleItemClick = (itemId: string | number, selected: boolean) => {
        const {onItemSelectionChange} = this.props;

        if (onItemSelectionChange) {
            onItemSelectionChange(itemId, selected);
        }
    };

    render() {
        const {onItemSelectionChange} = this.props;

        return (
            <MediaCardAdapter
                {...this.props}
                icon={SELECT_ICON}
                onItemClick={onItemSelectionChange}
                showCoverWhenSelected={true}
            />
        );
    }
}

export default MediaCardSelectionAdapter;
