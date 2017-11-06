// @flow
import React from 'react';
import {Breadcrumb} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import type {BreadcrumbItems} from './types';

type Props = {
    breadcrumb: ?BreadcrumbItems,
    onNavigate: (collectionId?: string | number) => void,
};

export default class BreadcrumbContainer extends React.PureComponent<Props> {
    handleNavigate = (collectionId?: string | number) => {
        this.props.onNavigate(collectionId);
    };

    render() {
        const {breadcrumb} = this.props;
        const Item = Breadcrumb.Item;
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

        const lastItem = breadcrumb[breadcrumb.length -1];
        const penultimateItem = breadcrumb[breadcrumb.length -2];

        return (
            <Breadcrumb onItemClick={this.handleNavigate}>
                <Item>{rootItemTitle}</Item>
                <Item value={penultimateItem.id}>...</Item>
                <Item>{lastItem.title}</Item>
            </Breadcrumb>
        );
    }
}
