// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import type {OverlayType} from './types';
import CollectionSection from './CollectionSection';
import MediaSection from './MediaSection';

type Props = {|
    locale: IObservableValue<string>,
    mediaListAdapters: Array<string>,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    mediaListStore: ListStore,
    collectionListStore: ListStore,
    collectionStore: CollectionStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
    overlayType: OverlayType,
|};

@observer
export default class MediaCollection extends React.Component<Props> {
    static defaultProps = {
        overlayType: 'overlay',
    };

    handleMediaClick = (mediaId: string | number) => {
        const {onMediaNavigate} = this.props;

        if (onMediaNavigate) {
            onMediaNavigate(mediaId);
        }
    };

    handleCollectionNavigate = (collectionId: ?string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    @action handleUpload = (media: Array<Object>) => {
        const {mediaListStore} = this.props;

        mediaListStore.reset();
        mediaListStore.reload();

        when(
            () => !mediaListStore.loading,
            (): void => media.forEach((mediaItem) => mediaListStore.select(mediaItem))
        );
    };

    render() {
        const {
            collectionListStore,
            collectionStore,
            locale,
            overlayType,
            mediaListAdapters,
            mediaListRef,
            mediaListStore,
        } = this.props;

        return (
            <MultiMediaDropzone
                collectionId={collectionStore.id}
                locale={locale}
                onUpload={this.handleUpload}
            >
                <CollectionSection
                    listStore={collectionListStore}
                    locale={locale}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    overlayType={overlayType}
                    resourceStore={collectionStore.resourceStore}
                />
                <Divider />
                <div>
                    <MediaSection
                        adapters={mediaListAdapters}
                        listStore={mediaListStore}
                        mediaListRef={mediaListRef}
                        onMediaClick={this.handleMediaClick}
                    />
                </div>
            </MultiMediaDropzone>
        );
    }
}
