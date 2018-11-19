// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaSelectionOverlay from '../MediaSelectionOverlay/MediaSelectionOverlay';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const USER_SETTINGS_KEY = 'media_selection_overlay';

type Props = {
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Array<Object>) => void,
};

@observer
export default class MultiMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
        open: false,
    };

    collectionId: IObservableValue<?string | number> = observable.box();
    mediaDatagridStore: DatagridStore;
    collectionDatagridStore: DatagridStore;

    constructor(props: Props) {
        super(props);

        this.createCollectionDatagridStore();
        this.createMediaDatagridStore();
    }

    componentWillUnmount() {
        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }
    }

    createCollectionDatagridStore() {
        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: observable.box(1),
                locale: this.props.locale,
                parentId: this.collectionId,
            }
        );
    }

    createMediaDatagridStore() {
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

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: observable.box(1),
                locale: this.props.locale,
                collection: this.collectionId,
            },
            options
        );
    }

    render() {
        const {
            excludedIds,
            onClose,
            onConfirm,
            open,
            locale,
        } = this.props;

        return (
            <MediaSelectionOverlay
                collectionDatagridStore={this.collectionDatagridStore}
                collectionId={this.collectionId}
                excludedIds={excludedIds}
                locale={locale}
                mediaDatagridStore={this.mediaDatagridStore}
                onClose={onClose}
                onConfirm={onConfirm}
                open={open}
            />
        );
    }
}
