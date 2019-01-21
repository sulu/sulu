// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import webspaceStore from '../../stores/WebspaceStore';
import type {Webspace} from '../../stores/WebspaceStore/types';

@observer
export default class PageTabs extends React.Component<ViewProps> {
    @observable webspace: Webspace;

    constructor(props: ViewProps) {
        super(props);

        webspaceStore.loadWebspace(this.props.router.attributes.webspace)
            .then(action((webspace) => {
                this.webspace = webspace;
            }));
    }

    render() {
        const props = {...this.props};

        const locales = this.webspace
            ? this.webspace.allLocalizations.map((localization) => localization.name)
            : [];

        return <ResourceTabs {...props} locales={locales} titleProperty="title" />;
    }
}
