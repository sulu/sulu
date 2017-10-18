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
        const Crumb = Breadcrumb.Crumb;
        const rootCrumbTitle = translate('sulu_media.all_media');

        if (!breadcrumb || !breadcrumb.length) {
            return (
                <Breadcrumb>
                    <Crumb>{rootCrumbTitle}</Crumb>
                </Breadcrumb>
            );
        } else if (breadcrumb.length === 1) {
            const firstCrumb = breadcrumb[0];

            return (
                <Breadcrumb>
                    <Crumb onClick={this.handleNavigate}>{rootCrumbTitle}</Crumb>
                    <Crumb>{firstCrumb.title}</Crumb>
                </Breadcrumb>
            );
        }

        const lastCrumb = breadcrumb[breadcrumb.length -1];
        const penultimateCrumb = breadcrumb[breadcrumb.length -2];

        return (
            <Breadcrumb>
                <Crumb onClick={this.handleNavigate}>{rootCrumbTitle}</Crumb>
                <Crumb value={penultimateCrumb.id} onClick={this.handleNavigate}>...</Crumb>
                <Crumb>{lastCrumb.title}</Crumb>
            </Breadcrumb>
        );
    }
}
