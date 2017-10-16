// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {DatagridAdapterProps} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'pencil';

@observer
export default class MediaCardOverviewAdapter extends React.Component<DatagridAdapterProps> {
    render() {
        return (
            <MediaCardAdapter
                {...this.props}
                icon={EDIT_ICON}
                showDownloadDropdown={true}
            />
        );
    }
}
