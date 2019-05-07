// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {Breadcrumb} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import type {BreadcrumbItem, BreadcrumbItems} from './types';

type Props = {
    resourceStore: ResourceStore,
    onNavigate: (collectionId?: string | number) => void,
};

export default @observer class CollectionBreadcrumb extends React.Component<Props> {
    static getCurrentCollectionItem(data: Object): BreadcrumbItem {
        return {
            id: data.id,
            title: data.title,
        };
    }

    @computed get breadcrumb(): ?BreadcrumbItems {
        const {resourceStore} = this.props;
        const {data} = resourceStore;

        if (!data._embedded) {
            return null;
        }

        const {
            _embedded: {
                breadcrumb,
            },
        } = data;
        const currentCollection = CollectionBreadcrumb.getCurrentCollectionItem(data);

        return breadcrumb ? [...breadcrumb, currentCollection] : [currentCollection];
    }

    handleNavigate = (collectionId?: string | number) => {
        this.props.onNavigate(collectionId);
    };

    render() {
        const Item = Breadcrumb.Item;
        const breadcrumb = this.breadcrumb;
        const rootItemTitle = translate('sulu_media.all_media');

        if (!breadcrumb || !breadcrumb.length) {
            return (
                <Breadcrumb>
                    <Item>{rootItemTitle}</Item>
                </Breadcrumb>
            );
        } else if (breadcrumb.length === 1) {
            const firstItem = breadcrumb[0];

            return (
                <Breadcrumb onItemClick={this.handleNavigate}>
                    <Item>{rootItemTitle}</Item>
                    <Item>{firstItem.title}</Item>
                </Breadcrumb>
            );
        }

        const lastItem = breadcrumb[breadcrumb.length - 1];
        const penultimateItem = breadcrumb[breadcrumb.length - 2];

        return (
            <Breadcrumb onItemClick={this.handleNavigate}>
                <Item>{rootItemTitle}</Item>
                <Item value={penultimateItem.id}>...</Item>
                <Item>{lastItem.title}</Item>
            </Breadcrumb>
        );
    }
}
