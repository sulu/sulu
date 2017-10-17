// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {Breadcrumb, Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionInfoStore from './stores/CollectionInfoStore';
import type {BreadcrumbItems} from './types';

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
    createBreadcrumb(breadcrumb: ?BreadcrumbItems) {
        const Crumb = Breadcrumb.Crumb;

        if (!breadcrumb || !breadcrumb.length) {
            return (
                <Breadcrumb>
                    <Crumb>Media</Crumb>
                </Breadcrumb>
            );
        } else if (breadcrumb.length === 1) {
            const firstCrumb = breadcrumb[0];

            return (
                <Breadcrumb>
                    <Crumb onClick={this.handleBreadcrumbNavigate}>Media</Crumb>
                    <Crumb>{firstCrumb.title}</Crumb>
                </Breadcrumb>
            );
        }

        const lastCrumb = breadcrumb[breadcrumb.length -1];
        const penultimateCrumb = breadcrumb[breadcrumb.length -2];

        return (
            <Breadcrumb>
                <Crumb onClick={this.handleBreadcrumbNavigate}>Media</Crumb>
                <Crumb value={penultimateCrumb.id} onClick={this.handleBreadcrumbNavigate}>...</Crumb>
                <Crumb>{lastCrumb.title}</Crumb>
            </Breadcrumb>
        );
    }

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
                {this.createBreadcrumb(breadcrumb)}
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
