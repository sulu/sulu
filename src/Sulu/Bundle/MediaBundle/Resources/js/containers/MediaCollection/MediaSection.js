//@flow
import React from 'react';
import type {ElementRef} from 'react';
import {List, ListStore} from 'sulu-admin-bundle/containers';

type Props = {|
    adapters: Array<string>,
    listStore: ListStore,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    onMediaClick: (mediaId: string | number) => void,
|};

export default class MediaSection extends React.PureComponent<Props> {
    handleMediaClick = (mediaId: string | number) => {
        this.props.onMediaClick(mediaId);
    };

    render() {
        const {
            adapters,
            listStore,
            mediaListRef,
        } = this.props;

        return (
            <List
                adapters={adapters}
                onItemClick={this.handleMediaClick}
                ref={mediaListRef}
                store={listStore}
            />
        );
    }
}
