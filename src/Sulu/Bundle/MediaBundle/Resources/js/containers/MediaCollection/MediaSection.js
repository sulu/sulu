//@flow
import React from 'react';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import type {ElementRef} from 'react';

type Props = {|
    adapters: Array<string>,
    listStore: ListStore,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    onMediaClick?: (mediaId: string | number) => void,
|};

export default class MediaSection extends React.PureComponent<Props> {
    render() {
        const {
            adapters,
            listStore,
            mediaListRef,
            onMediaClick,
        } = this.props;

        return (
            <List
                adapters={adapters}
                onItemClick={onMediaClick}
                ref={mediaListRef}
                store={listStore}
            />
        );
    }
}
