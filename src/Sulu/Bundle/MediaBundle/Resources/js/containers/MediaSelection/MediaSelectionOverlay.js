// @flow
import React from 'react';
import {action, autorun, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
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
export default class MediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        open: false,
        excludedIds: [],
    };

    mediaPage: IObservableValue<number> = observable.box(1);
    collectionPage: IObservableValue<number> = observable.box(1);
    collectionId: IObservableValue<?string | number> = observable.box();
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    @observable collectionStore: CollectionStore;
    overlayDisposer: () => void;

    constructor(props: Props) {
        super(props);

        const {open} = this.props;

        if (open) {
            this.initialize();
        }
    }

    componentWillUnmount() {
        this.destroy();
    }

    componentDidUpdate(prevProps: Props) {
        const {open} = this.props;

        if (!prevProps.open && open) {
            this.initialize();
        }
    }

    @computed get locale(): IObservableValue<string> {
        return this.props.locale;
    }

    @action initialize() {
        this.createCollectionDatagridStore();
        this.createMediaDatagridStore();
        this.overlayDisposer = autorun(this.createCollectionStore);
        this.mediaPage.set(1);
        this.collectionPage.set(1);
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

        this.collectionId.set(undefined);
    }

    @action setMediaPage(page: number) {
        this.mediaPage.set(page);
    }

    @action setCollectionPage(page: number) {
        this.collectionPage.set(page);
    }

    @action createCollectionDatagridStore() {
        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page: this.collectionPage,
                locale: this.locale,
                parent: this.collectionId,
            }
        );
    }

    createCollectionStore = () => {
        this.setCollectionStore(new CollectionStore(this.collectionId.get(), this.locale));
    };

    @action setCollectionStore(collectionStore: CollectionStore) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = collectionStore;
    }

    @action createMediaDatagridStore() {
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

        if (excludedIds.length) {
            options.excluded = excludedIds.join(',');
        }

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page: this.mediaPage,
                locale: this.locale,
                collection: this.collectionId,
            },
            options
        );
    }

    @action handleCollectionNavigate = (collectionId: ?string | number) => {
        this.mediaDatagridStore.clearData();
        this.collectionDatagridStore.clearData();
        this.setMediaPage(1);
        this.setCollectionPage(1);
        this.collectionId.set(collectionId);
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
        this.mediaDatagridStore.clearSelection();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.mediaDatagridStore.selections);
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
                        locale={locale}
                        mediaDatagridAdapters={['media_card_selection']}
                        mediaDatagridStore={this.mediaDatagridStore}
                        collectionDatagridStore={this.collectionDatagridStore}
                        collectionStore={this.collectionStore}
                        onCollectionNavigate={this.handleCollectionNavigate}
                        overlayType="dialog"
                    />
                </div>
            </Overlay>
        );
    }
}
