// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionInfoStore from './stores/CollectionInfoStore';
import BreadcrumbContainer from './BreadcrumbContainer';

type Props = {
    page: observable,
    locale: observable,
    mediaView: string,
    mediaStore: DatagridStore,
    collectionStore: DatagridStore,
    collectionInfoStore: CollectionInfoStore,
    onCollectionNavigate: (collectionId?: string | number) => void,
};

@observer
export default class MediaContainer extends React.PureComponent<Props> {
    handleCollectionClick = (collectionId: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleBreadcrumbNavigate = (collectionId?: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    render() {
        const {
            mediaView,
            mediaStore,
            collectionStore,
            collectionInfoStore,
        } = this.props;
        const {breadcrumb} = collectionInfoStore;

        return (
            <div>
                {!collectionInfoStore.loading &&
                    <BreadcrumbContainer
                        breadcrumb={breadcrumb}
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
                    views={[mediaView]}
                    store={mediaStore}
                />
            </div>
        );
    }
}
