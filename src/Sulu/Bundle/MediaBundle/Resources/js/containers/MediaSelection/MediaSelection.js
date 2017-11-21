// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay, MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import MediaSelectionStore from './stores/MediaSelectionStore';
import MediaSelectionItem from './MediaSelectionItem';
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
    mediaPage: observable = observable();
    collectionPage: observable = observable();
    @observable collectionId: ?string | number;
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    collectionStore: CollectionStore;
    mediaSelectionStore: MediaSelectionStore;
    selectedMediaIds: Array<string | number> = [];
    @observable overlayOpen: boolean = false;
    overlayDisposer: () => void;

    componentWillMount() {
        const {
            value,
            locale,
        } = this.props;

        this.mediaSelectionStore = new MediaSelectionStore(value, locale);
    }

    @action openMediaOverlay() {
        this.overlayOpen = true;

        this.overlayDisposer = autorun(this.createStores);
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;

        this.selectedMediaIds = [];
        this.overlayDisposer();
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
        this.setMediaPage(1);
        this.setCollectionPage(1);

        this.createCollectionStore(this.collectionId, this.props.locale);
        this.createMediaDatagridStore(this.collectionId, this.mediaPage, this.props.locale);
        this.createCollectionDatagridStore(this.collectionId, this.collectionPage, this.props.locale);
    };

    @action createCollectionDatagridStore(collectionId: ?observable, page: observable, locale: string) {
        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }

        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page,
                locale,
            },
            (collectionId) ? {parent: collectionId} : undefined
        );
    }

    createCollectionStore = (collectionId: ?observable, locale: string) => {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new CollectionStore(collectionId, locale);
    };

    @action createMediaDatagridStore(collectionId: ?observable, page: observable, locale: string) {
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

        if (this.mediaSelectionStore.selectedMediaIds.length) {
            options.exclude = this.mediaSelectionStore.selectedMediaIds.join(',');
        }

        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page,
                locale,
            },
            options,
            true,
            this.handleMediaSelection,
            this.selectedMediaIds
        );
    }

    addMediaToSelectionStore() {
        this.selectedMediaIds.forEach((mediaId) => {
            const media = this.mediaDatagridStore.data.find((item) => item.id === mediaId);

            if (media) {
                this.mediaSelectionStore.add(media);
            }
        });
    }

    handleMediaSelection = (mediaId: string | number, selected: boolean) => {
        if (selected) {
            this.selectedMediaIds.push(mediaId);
        } else {
            this.selectedMediaIds = this.selectedMediaIds.filter((id) => id !== mediaId);
        }
    };

    handleMediaRemove = (mediaId: string | number) => {
        this.mediaSelectionStore.removeById(mediaId);
        this.props.onChange(this.mediaSelectionStore.selectedMediaIds);
    };

    handleMediaSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.mediaSelectionStore.move(oldItemIndex, newItemIndex);
        this.props.onChange(this.mediaSelectionStore.selectedMediaIds);
    };

    handleOverlayOpen = () => {
        this.openMediaOverlay();
    };

    handleOverlayClose = () => {
        this.closeMediaOverlay();
    };

    handleOverlayCollectionNavigate = (collectionId: ?string | number) => {
        this.setCollectionId(collectionId);
    };

    handleOverlayConfirm = () => {
        this.addMediaToSelectionStore();
        this.props.onChange(this.mediaSelectionStore.selectedMediaIds);
        this.closeMediaOverlay();
    };

    handleSelectionReset = () => {
        this.selectedMediaIds = [];
        this.mediaDatagridStore.deselectEntirePage();
    };

    render() {
        const {locale} = this.props;
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
                    {this.mediaSelectionStore.selectedMedia.map((selectedMedia, index) => {
                        const {
                            id,
                            title,
                            thumbnail,
                        } = selectedMedia;

                        return (
                            <MultiItemSelection.Item
                                key={id}
                                id={id}
                                index={index + 1}
                            >
                                <MediaSelectionItem thumbnail={thumbnail}>
                                    {title}
                                </MediaSelectionItem>
                            </MultiItemSelection.Item>
                        );
                    })}
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
                        <MediaCollection
                            page={this.collectionPage}
                            locale={locale}
                            mediaViews={['media_card_selection']}
                            mediaDatagridStore={this.mediaDatagridStore}
                            collectionDatagridStore={this.collectionDatagridStore}
                            collectionStore={this.collectionStore}
                            onCollectionNavigate={this.handleOverlayCollectionNavigate}
                        />
                    </div>
                </Overlay>
            </div>
        );
    }
}
