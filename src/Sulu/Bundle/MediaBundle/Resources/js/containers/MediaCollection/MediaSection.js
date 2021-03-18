//@flow
import React from 'react';
import type {ElementRef} from 'react';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {Button, ButtonGroup} from 'sulu-admin-bundle/components';

type Props = {|
    adapters: Array<string>,
    listStore: ListStore,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    onMediaClick: (mediaId: string | number) => void,
    onUploadOverlayOpen: () => void,
    uploadable: boolean,
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
            onUploadOverlayOpen,
            uploadable,
        } = this.props;

        return (
            <List
                adapters={adapters}
                buttons={[
                        <ButtonGroup key="upload-media">
                            <Button icon="su-upload" onClick={onUploadOverlayOpen}>
                                Upload File
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
