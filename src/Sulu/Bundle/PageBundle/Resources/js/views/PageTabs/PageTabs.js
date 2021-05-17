// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import webspaceStore from '../../stores/webspaceStore';
import type {ViewProps} from 'sulu-admin-bundle/containers';

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

        if (typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string!');
        }

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
