// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import MediaCollection from '../MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaSelectionOverlayStyles from './mediaSelectionOverlay.scss';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const USER_SETTINGS_KEY = 'media_selection_overlay';

type Props = {|
    open: boolean,
    locale: IObservableValue<string>,
    collectionId: IObservableValue<?string | number>,
    collectionDatagridStore: DatagridStore,
    mediaDatagridStore: DatagridStore,
    onClose: () => void,
    onConfirm: (selectedMedia: Array<Object>) => void,
|};

@observer
export default class MediaSelectionOverlay extends React.Component<Props> {
    @observable collectionStore: CollectionStore;
    updateCollectionStoreDisposer: () => void;

    static createCollectionDatagridStore(
        collectionId: IObservableValue<?string | number>,
        locale: IObservableValue<string>
    ) {
        return new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            COLLECTIONS_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: observable.box(),
                locale: locale,
                parentId: collectionId,
            }
        );
    }

    static createMediaDatagridStore(
        collectionId: IObservableValue<?string | number>,
        excludedIdString: IObservableValue<string>,
        locale: IObservableValue<string>
    ) {
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

        return new DatagridStore(
            MEDIA_RESOURCE_KEY,
            MEDIA_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: observable.box(),
                collection: collectionId,
                excluded: excludedIdString,
                locale: locale,
            },
            options
        );
    }

    constructor(props: Props) {
        super(props);

        this.updateCollectionStoreDisposer = autorun(() => this.updateCollectionStore(this.props.collectionId.get()));
    }

    componentDidUpdate(prevProps: Props) {
        const {mediaDatagridStore, open} = this.props;

        if (prevProps.open === true && open === false) {
            mediaDatagridStore.clearSelection();
        }
    }

    componentWillUnmount() {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        if (this.updateCollectionStoreDisposer) {
            this.updateCollectionStoreDisposer();
        }
    }

    @action updateCollectionStore(collectionId: ?string | number) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new CollectionStore(collectionId, this.props.locale);
    }

    @action handleCollectionNavigate = (collectionId: ?string | number) => {
        this.props.collectionId.set(collectionId);

        this.props.collectionDatagridStore.clear();
        this.props.collectionDatagridStore.setPage(1);

        this.props.mediaDatagridStore.clear();
        this.props.mediaDatagridStore.setPage(1);
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleSelectionReset = () => {
        this.props.mediaDatagridStore.clearSelection();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.props.mediaDatagridStore.selections);
    };

    render() {
        const {
            collectionDatagridStore,
            mediaDatagridStore,
            open,
            locale,
        } = this.props;

        const overlayActions = [{
            title: translate('sulu_media.reset_selection'),
            onClick: this.handleSelectionReset,
        }];

        return (
            <Overlay
                actions={overlayActions}
                confirmDisabled={!mediaDatagridStore.selections.length}
                confirmText={translate('sulu_admin.confirm')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_media.select_media_plural')}
            >
                <div className={mediaSelectionOverlayStyles.overlay}>
                    <MediaCollection
                        collectionDatagridStore={collectionDatagridStore}
                        collectionStore={this.collectionStore}
                        locale={locale}
                        mediaDatagridAdapters={['media_card_selection']}
                        mediaDatagridStore={mediaDatagridStore}
                        onCollectionNavigate={this.handleCollectionNavigate}
                        overlayType="dialog"
                    />
                </div>
            </Overlay>
        );
    }
}
