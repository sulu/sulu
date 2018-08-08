// @flow
import React from 'react';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import pageSettingsStyles from './pageSettings.scss';

export default class PageSettings extends React.Component<ViewProps> {
    render() {
        return (
            <div className={pageSettingsStyles.pageSettings}>
                <h1>Test</h1>
            </div>
        );
    }
}
