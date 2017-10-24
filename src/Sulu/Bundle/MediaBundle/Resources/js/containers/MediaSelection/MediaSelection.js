// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay, MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import {CollectionInfoStore, MediaContainer} from '../MediaContainer';
import mediaSelectionStyles from './mediaSelection.scss';

const ADD_ICON = 'plus';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    locale: string,
    value: Array<string | number>,
    onChange: (ids: Array<string | number>) => void,
};

@observer
export default class MediaSelection extends React.PureComponent<Props> {
    locale: observable = observable();
    mediaPage: observable = observable();
    collectionPage: observable = observable();
    @observable collectionId: ?string | number;
    @observable mediaStore: DatagridStore;
    @observable collectionStore: DatagridStore;
    collectionInfoStore: CollectionInfoStore;
    @observable overlayOpen: boolean = false;
    disposer: () => void;

    @action openMediaOverlay() {
        this.overlayOpen = true;
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;
    }

    @action setLocale(locale: string) {
        this.locale.set(locale);
    }

    @action setMediaPage(page: number) {
        this.mediaPage.set(page);
    }

    @action setCollectionPage(page: number) {
        this.collectionPage.set(page);
    }

    @action setCollectionId(id: ?string | number) {
        this.collectionId = id;
    }

    createStores = () => {
        // TODO: locale should be dynamic
        this.setLocale(this.props.locale);
        this.setMediaPage(1);
        this.setCollectionPage(1);

        this.createMediaStore(this.collectionId, this.mediaPage, this.locale);
        this.createCollectionStore(this.collectionId, this.collectionPage, this.locale);
        this.createCollectionInfoStore(this.collectionId, this.locale);
    };

    @action createCollectionStore(collectionId: ?observable, page: observable, locale: string) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page,
                locale,
            },
            (collectionId) ? {parent: collectionId} : undefined
        );
    }

    createCollectionInfoStore = (collectionId: ?observable, locale: string) => {
        if (this.collectionInfoStore) {
            this.collectionInfoStore.destroy();
        }

        this.collectionInfoStore = new CollectionInfoStore(collectionId, locale);
    };

    @action createMediaStore(collectionId: ?observable, page: observable, locale: string) {
        const options = {};
        options.fields = [
            'id',
            'type',
            'name',
            'size',
            'title',
            'mimeType',
            'subVersion',
            'thumbnails',
        ].join(',');

        page.set(1);

        if (collectionId) {
            options.collection = collectionId;
        }

        if (this.mediaStore) {
            this.mediaStore.destroy();
        }

        this.mediaStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page,
                locale,
            },
            options,
            true
        );
    }

    handleMediaRemove = (itemId: string | number) => {

    };

    handleMediaSorted = () => {

    };

    handleOverlayOpen = () => {
        this.openMediaOverlay();
        this.disposer = autorun(this.createStores);
    };

    handleOverlayClose = () => {
        this.closeMediaOverlay();
    };

    handleOverlayCollectionNavigate = (collectionId: ?string | number) => {
        this.setCollectionId(collectionId);
    };

    handleOverlayConfirm = () => {

    };

    handleSelectionReset = () => {

    };

    render() {
        const actions = [
            {
                title: translate('sulu_media.reset_selection'),
                onClick: this.handleSelectionReset,
            },
        ];

        return (
            <div>
                <MultiItemSelection
                    label={translate('sulu_media.select_media')}
                    onItemRemove={this.handleMediaRemove}
                    leftButton={{
                        icon: ADD_ICON,
                        onClick: this.handleOverlayOpen,
                    }}
                    onItemsSorted={this.handleMediaSorted}
                >
                </MultiItemSelection>
                <Overlay
                    open={this.overlayOpen}
                    title={translate('sulu_media.select_media')}
                    onClose={this.handleOverlayClose}
                    confirmText={translate('sulu_admin.confirm')}
                    onConfirm={this.handleOverlayConfirm}
                    actions={actions}
                >
                    <div className={mediaSelectionStyles.mediaOverlay}>
                        <MediaContainer
                            page={this.collectionPage}
                            locale={this.locale}
                            mediaView="media_card_selection"
                            mediaStore={this.mediaStore}
                            collectionStore={this.collectionStore}
                            collectionInfoStore={this.collectionInfoStore}
                            onCollectionNavigate={this.handleOverlayCollectionNavigate}
                        />
                    </div>
                </Overlay>
            </div>
        );
    }
}
