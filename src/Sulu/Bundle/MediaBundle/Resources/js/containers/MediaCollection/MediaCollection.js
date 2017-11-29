// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import CollectionBreadcrumb from './CollectionBreadcrumb';

type Props = {
    page: observable,
    locale: observable,
    mediaDatagridAdapters: Array<string>,
    mediaDatagridStore: DatagridStore,
    collectionDatagridStore: DatagridStore,
    collectionStore: CollectionStore,
    onCollectionNavigate: (collectionId?: string | number) => void,
};

@observer
export default class MediaCollection extends React.PureComponent<Props> {
    static defaultProps = {
        mediaViews: [],
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
            <div>
                {!collectionStore.loading &&
                    <CollectionBreadcrumb
                        breadcrumb={collectionStore.breadcrumb}
                        onNavigate={this.handleBreadcrumbNavigate}
                    />
                }
                <Divider />
                <Datagrid
                    adapters={mediaDatagridAdapters}
                    store={mediaDatagridStore}
                />
            </div>
        );
    }
}
