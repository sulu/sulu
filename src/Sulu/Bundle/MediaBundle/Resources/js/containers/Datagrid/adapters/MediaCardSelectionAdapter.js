// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const SELECT_ICON = 'check';

@observer
export default class MediaCardSelectionAdapter extends AbstractAdapter {
    static getLoadingStrategy: () => string = () => { return 'infiniteScroll'; };
    static getStorageStrategy: () => string = () => { return 'flat'; };

    render() {
        return (
            <MediaCardAdapter
                {...this.props}
                icon={SELECT_ICON}
            />
        );
    }
}
