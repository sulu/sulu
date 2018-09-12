// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStructureStrategy, InfiniteLoadingStrategy} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const SELECT_ICON = 'su-check';

@observer
export default class MediaCardSelectionAdapter extends AbstractAdapter {
    static LoadingStrategy = InfiniteLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-th-large';

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
