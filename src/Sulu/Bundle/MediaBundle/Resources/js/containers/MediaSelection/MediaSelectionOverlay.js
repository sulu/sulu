// @flow
import React from 'react';
import {action, autorun, computed, observable, observe} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaSelectionOverlayStyles from './mediaSelectionOverlay.scss';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<string | number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Array<Object>) => void,
};

@observer
export default class MediaSelectionOverlay extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
        excludedIds: [],
    };

    mediaPage: observable = observable();
    collectionPage: observable = observable();
    @observable collectionId: ?string | number;
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    collectionStore: CollectionStore;
    selectedMedia: Array<Object> = [];
    overlayDisposer: () => void;
    mediaSelectionsObservationDisposer: () => void;

    componentWillMount() {
        const {open} = this.props;

        if (open) {
            this.overlayDisposer = autorun(this.createStores);
        }
    }

    componentWillUnmount() {
        this.destroy();
    }

    componentWillReceiveProps(nextProps: Props) {
        const {open} = this.props;

        if (!open && nextProps.open) {
            this.overlayDisposer = autorun(this.createStores);
        }
    }

    @computed get locale(): IObservableValue<string> {
        return this.props.locale;
    }

    @action destroy() {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }

        if (this.overlayDisposer) {
            this.overlayDisposer();
        }

        if (this.mediaSelectionsObservationDisposer) {
            this.mediaSelectionsObservationDisposer();
        }

        this.selectedMedia = [];
        this.collectionId = undefined;
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

        this.createCollectionStore(this.collectionId, this.locale);
        this.createMediaDatagridStore(this.collectionId, this.mediaPage, this.locale);
        this.createCollectionDatagridStore(this.collectionId, this.collectionPage, this.locale);
    };

    @action createCollectionDatagridStore(
        collectionId: ?string | number,
        page: IObservableValue<number>,
        locale: IObservableValue<string>
    ) {
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

    createCollectionStore = (collectionId: ?string | number, locale: IObservableValue<string>) => {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new CollectionStore(collectionId, locale);
    };

    @action createMediaDatagridStore(
        collectionId: ?string | number,
        page: IObservableValue<number>,
        locale: IObservableValue<string>
    ) {
        const {excludedIds} = this.props;
        const options = {};

        options.limit = 50;
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

        if (excludedIds.length) {
            options.excluded = excludedIds.join(',');
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
            options
        );

        this.mediaSelectionsObservationDisposer = observe(
            this.mediaDatagridStore.selections,
            this.handleMediaSelectionChanges
        );

        this.selectedMedia.forEach((media) => this.mediaDatagridStore.select(media.id));
    }

    handleMediaSelectionChanges = (change: observable) => {
        const mediaId = (change.added.length) ? change.added[0] : change.removed[0];
        const selected = !!change.added.length;

        if (selected) {
            const media = this.mediaDatagridStore.data.find((entry) => entry.id === mediaId);

            if (media) {
                this.selectedMedia.push(media);
            }
        } else {
            this.selectedMedia = this.selectedMedia.filter((media) => media.id !== mediaId);
        }
    };

    handleCollectionNavigate = (collectionId: ?string | number) => {
        this.setCollectionId(collectionId);
    };

    handleClose = () => {
        const {
            open,
            onClose,
        } = this.props;

        if (open) {
            this.destroy();
        }

        onClose();
    };

    handleSelectionReset = () => {
        this.selectedMedia = [];
        this.mediaDatagridStore.deselectEntirePage();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.selectedMedia);
        this.destroy();
    };

    render() {
        const {
            open,
            locale,
        } = this.props;
        const actions = [
            {
                title: translate('sulu_media.reset_selection'),
                onClick: this.handleSelectionReset,
            },
        ];

        return (
            <Overlay
                open={open}
                title={translate('sulu_media.select_media')}
                onClose={this.handleClose}
                confirmText={translate('sulu_admin.confirm')}
                onConfirm={this.handleConfirm}
                actions={actions}
            >
                <div className={mediaSelectionOverlayStyles.overlay}>
                    <MediaCollection
                        page={this.collectionPage}
                        locale={locale}
                        mediaDatagridAdapters={['media_card_selection']}
                        mediaDatagridStore={this.mediaDatagridStore}
                        collectionDatagridStore={this.collectionDatagridStore}
                        collectionStore={this.collectionStore}
                        onCollectionNavigate={this.handleCollectionNavigate}
                    />
                </div>
            </Overlay>
        );
    }
}
