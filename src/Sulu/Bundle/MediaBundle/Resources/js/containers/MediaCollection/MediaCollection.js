// @flow
import React from 'react';
import {when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import type {OverlayType} from './types';
import CollectionSection from './CollectionSection';
import MediaSection from './MediaSection';

type Props = {
    collectionDatagridStore: DatagridStore,
    collectionStore: CollectionStore,
    locale: IObservableValue<string>,
    mediaDatagridAdapters: Array<string>,
    mediaDatagridStore: DatagridStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
    overlayType: OverlayType,
};

@observer
export default class MediaCollection extends React.Component<Props> {
    static defaultProps = {
        mediaViews: [],
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

    handleUpload = (media: Array<Object>) => {
        const {mediaDatagridStore} = this.props;

        mediaDatagridStore.reload();
        when(
            () => !mediaDatagridStore.loading,
            (): void => media.forEach((mediaItem) => mediaDatagridStore.select(mediaItem.id))
        );
    };

    render() {
        const {
            locale,
            overlayType,
            collectionStore,
            mediaDatagridStore,
            mediaDatagridAdapters,
            collectionDatagridStore,
        } = this.props;

        return (
            <MultiMediaDropzone
                collectionId={collectionStore.id}
                locale={locale}
                onUpload={this.handleUpload}
            >
                <CollectionSection
                    datagridStore={collectionDatagridStore}
                    locale={locale}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    overlayType={overlayType}
                    resourceStore={collectionStore.resourceStore}
                />
                <Divider />
                <div>
                    <MediaSection
                        adapters={mediaDatagridAdapters}
                        datagridStore={mediaDatagridStore}
                        onMediaClick={this.handleMediaClick}
                    />
                </div>
            </MultiMediaDropzone>
        );
    }
}
