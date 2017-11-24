// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'pencil';

@observer
export default class MediaCardOverviewAdapter extends AbstractAdapter {
    static getLoadingStrategy: () => string = () => { return 'infiniteScroll'; };
    static getStorageStrategy: () => string = () => { return 'flat'; };

    render() {
        return (
            <MediaCardAdapter
                {...this.props}
                icon={EDIT_ICON}
            />
        );
    }
}
