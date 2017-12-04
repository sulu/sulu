// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import CollectionBreadcrumb from './CollectionBreadcrumb';

type Props = {
    page: IObservableValue<number>,
    locale: IObservableValue<string>,
    mediaDatagridAdapters: Array<string>,
    mediaDatagridStore: DatagridStore,
    collectionDatagridStore: DatagridStore,
    collectionStore: CollectionStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
};

@observer
export default class MediaCollection extends React.PureComponent<Props> {
    static defaultProps = {
        mediaViews: [],
    };

    handleMediaClick = (mediaId: string | number) => {
        const {onMediaNavigate} = this.props;

        if (onMediaNavigate) {
            onMediaNavigate(mediaId);
        }
    };

    handleCollectionClick = (collectionId: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleBreadcrumbNavigate = (collectionId?: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    render() {
        const {
            mediaDatagridAdapters,
            mediaDatagridStore,
            collectionStore,
            collectionDatagridStore,
        } = this.props;

        return (
            <MultiMediaDropzone disabled={collectionStore.loading}>
                {!collectionStore.loading &&
                    <CollectionBreadcrumb
                        breadcrumb={collectionStore.breadcrumb}
                        onNavigate={this.handleBreadcrumbNavigate}
                    />
                }
                <Datagrid
                    adapters={['folder']}
                    store={collectionDatagridStore}
                    onItemClick={this.handleCollectionClick}
                />
                <Divider />
                <Datagrid
                    adapters={mediaDatagridAdapters}
                    store={mediaDatagridStore}
                    onItemClick={this.handleMediaClick}
                />
            </MultiMediaDropzone>
        );
    }
}
