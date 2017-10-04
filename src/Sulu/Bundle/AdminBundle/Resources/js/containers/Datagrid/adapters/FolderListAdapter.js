// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import type {AdapterProps} from '../types';
import {translate} from '../../../services/Translator';

@observer
export default class FolderListAdapter extends React.Component<AdapterProps> {
    getInfoText(item: Object) {
        const label = (item.objectCount === 1)
            ? translate('sulu_admin.object')
            : translate('sulu_admin.objects');

        return `${item.objectCount} ${label}`;
    }

    render() {
        const {
            data,
            onItemClick,
        } = this.props;

        return (
            <FolderList onFolderClick={onItemClick}>
                {data.map((item: Object) => (
                    <FolderList.Folder
                        key={item.id}
                        id={item.id}
                        title={item.title}
                        info={this.getInfoText(item)}
                    />
                ))}
            </FolderList>
        );
    }
}
