//@flow
import React from 'react';
import type {ElementRef} from 'react';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {Button, ButtonGroup} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';

type Props = {|
    adapters: Array<string>,
    listStore: ListStore,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    onMediaClick: (mediaId: string | number) => void,
    onUploadClick: (() => void) | null,
|};

export default class MediaSection extends React.PureComponent<Props> {
    handleMediaClick = (mediaId: string | number) => {
        this.props.onMediaClick(mediaId);
    };

    handleUploadClick = () => {
        if (this.props.onUploadClick) {
            this.props.onUploadClick();
        }
    };

    render() {
        const {
            adapters,
            listStore,
            mediaListRef,
            onUploadClick,
        } = this.props;

        return (
            <List
                adapters={adapters}
                buttons={[
                    onUploadClick && (
                        <ButtonGroup key="upload-media">
                            <Button icon="su-upload" onClick={this.handleUploadClick}>
                                {translate('sulu_media.upload')}
                            </Button>
                        </ButtonGroup>
                    ),
                ]}

                onItemClick={this.handleMediaClick}
                ref={mediaListRef}
                store={listStore}
            />
        );
    }
}
