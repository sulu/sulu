// @flow
import React from 'react';
import {action, when} from 'mobx';
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import CollectionSection from './CollectionSection';
import type {OverlayType} from './types';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';

type Props = {|
    className?: string,
    collectionListStore: ListStore,
    collectionStore: CollectionStore,
    hideUploadAction: boolean,
    locale: IObservableValue<string>,
    mediaListAdapters: Array<string>,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    mediaListStore: ListStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
    onUploadError?: (errors: Array<Object>) => void,
    onUploadOverlayClose: () => void,
    onUploadOverlayOpen: () => void,
    overlayType: OverlayType,
    uploadOverlayOpen: boolean,
|};

@observer
class MediaCollection extends React.Component<Props> {
    static defaultProps = {
        hideUploadAction: false,
        overlayType: 'overlay',
    };

    static addable: boolean = true;
    static deletable: boolean = true;
    static editable: boolean = true;
    static securable: boolean = true;

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

    @action handleUploadError = (errorResponses: Array<Object>) => {
        const {mediaListStore, onUploadError} = this.props;

        if (onUploadError) {
            onUploadError(errorResponses);
        }

        mediaListStore.reset();
        mediaListStore.reload();
    };

    render() {
        const {
            className,
            collectionListStore,
            collectionStore,
            hideUploadAction,
            locale,
            overlayType,
            mediaListAdapters,
            mediaListRef,
            mediaListStore,
            onMediaNavigate,
            onUploadOverlayClose,
            onUploadOverlayOpen,
            uploadOverlayOpen,
        } = this.props;

        const {locked, permissions} = collectionStore;
        const listActions = [];

        const addable = !locked && (permissions.add !== undefined ? permissions.add : MediaCollection.addable);
        const editable = !locked && (permissions.edit !== undefined ? permissions.edit : MediaCollection.editable);
        const deletable = !locked
            && (permissions.delete !== undefined ? permissions.delete : MediaCollection.deletable);
        const securable = !locked
            && (permissions.security !== undefined ? permissions.security : MediaCollection.securable);

        if (addable && !hideUploadAction) {
            listActions.push({
                disabled: collectionStore.loading,
                icon: 'su-upload',
                label: translate('sulu_media.upload_file'),
                onClick: onUploadOverlayOpen,
            });
        }

        return (
            <MultiMediaDropzone
                className={className}
                collectionId={collectionStore.id}
                disabled={collectionStore.loading || !addable}
                locale={locale}
                onClose={onUploadOverlayClose}
                onOpen={onUploadOverlayOpen}
                onUpload={this.handleUpload}
                onUploadError={this.handleUploadError}
                open={uploadOverlayOpen}
            >
                <CollectionSection
                    addable={addable}
                    deletable={deletable}
                    editable={editable}
                    listStore={collectionListStore}
                    locale={locale}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    overlayType={overlayType}
                    resourceStore={collectionStore.resourceStore}
                    securable={securable}
                />
                <Divider />
                <List
                    actions={listActions}
                    adapters={mediaListAdapters}
                    onItemClick={onMediaNavigate}
                    ref={mediaListRef}
                    store={mediaListStore}
                />
            </MultiMediaDropzone>
        );
    }
}

export default MediaCollection;
