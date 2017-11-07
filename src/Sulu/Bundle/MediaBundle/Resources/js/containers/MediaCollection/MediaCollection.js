// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionInfoStore from './stores/CollectionInfoStore';
import BreadcrumbBuilder from './BreadcrumbBuilder';

type Props = {
    page: observable,
    locale: observable,
    mediaViews: Array<string>,
    mediaStore: DatagridStore,
    collectionStore: DatagridStore,
    collectionInfoStore: CollectionInfoStore,
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
            mediaStore,
            collectionStore,
            collectionInfoStore,
        } = this.props;

        return (
            <div>
                {!collectionInfoStore.loading &&
                    <BreadcrumbBuilder
                        breadcrumb={collectionInfoStore.breadcrumb}
                        onNavigate={this.handleBreadcrumbNavigate}
                    />
                }
                <Datagrid
                    views={['folder']}
                    store={collectionStore}
                    onItemClick={this.handleCollectionClick}
                />
                <Divider />
                <Datagrid
                    views={mediaViews}
                    store={mediaStore}
                />
            </div>
        );
    }
}
