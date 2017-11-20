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
    mediaViews: Array<string>,
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
            mediaViews,
            collectionStore,
            mediaDatagridStore,
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
                <Datagrid
                    views={['folder']}
                    store={collectionDatagridStore}
                    onItemClick={this.handleCollectionClick}
                />
                <Divider />
                <Datagrid
                    views={mediaViews}
                    store={mediaDatagridStore}
                />
            </div>
        );
    }
}
