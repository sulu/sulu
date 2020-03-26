// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import webspaceStore from '../../stores/webspaceStore';

@observer
class PageTabs extends React.Component<ViewProps> {
    render() {
        const props = {...this.props};

        const {
            router: {
                attributes: {
                    webspace,
                },
            },
        } = this.props;

        return (
            <ResourceTabs
                {...props}
                locales={webspaceStore.getWebspace(webspace).allLocalizations.map((localization) => localization.name)}
                titleProperty="title"
            />
        );
    }
}

export default PageTabs;
