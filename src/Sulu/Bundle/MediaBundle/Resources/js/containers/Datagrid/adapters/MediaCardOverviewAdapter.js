// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {DatagridAdapterProps} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

@observer
export default class MediaCardOverviewAdapter extends React.Component<DatagridAdapterProps> {
    render() {
        return <MediaCardAdapter {...this.props} />;
    }
}
