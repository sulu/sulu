// @flow
import React from 'react';
import {action} from 'mobx';
import {observer} from 'mobx-react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import {Route} from 'sulu-admin-bundle/services';
import webspaceStore from '../../stores/webspaceStore';
import type {AttributeMap} from 'sulu-admin-bundle/services';
import type {ViewProps} from 'sulu-admin-bundle/containers';

@observer
class WebspaceSettingTabs extends React.Component<ViewProps> {
    static getDerivedRouteAttributes(route: Route, attributes: AttributeMap) {
        // there is only one setting per webspace, so the key of the webspace is its id
        return {id: attributes.webspace};
    }

    constructor(props: ViewProps) {
        super(props);

        this.updateId();
    }

    // Attributes passed to the router win over the derived ones, so the id of the previously selected webspace stays
    // when the webspace is changed. That has to be corrected before the ResourceTabs load the settings.
    @action updateId() {
        const {router} = this.props;

        if (router.attributes.id !== router.attributes.webspace) {
            router.attributes.id = router.attributes.webspace;
        }
    }

    render() {
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
                {...this.props}
                locales={webspaceStore.getWebspace(webspace).allLocalizations.map((localization) => localization.name)}
                titleProperty=""
            />
        );
    }
}

export default WebspaceSettingTabs;
