// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStrategy, InfiniteScrollingStrategy} from 'sulu-admin-bundle/containers';
import type {LoadingStrategyInterface, StructureStrategyInterface} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const SELECT_ICON = 'check';

@observer
export default class MediaCardSelectionAdapter extends AbstractAdapter {
    static getLoadingStrategy(): LoadingStrategyInterface {
        return new InfiniteScrollingStrategy();
    }

    static getStructureStrategy(): StructureStrategyInterface {
        return new FlatStrategy();
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
                onItemClick={onItemSelectionChange}
                icon={SELECT_ICON}
                showCoverWhenSelected={true}
            />
        );
    }
}
