// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import type {AdapterProps, DataItem} from '../types';
import {translate} from '../../../services/Translator';

@observer
export default class FolderListAdapter extends React.Component<AdapterProps> {
    getInfoText(item: DataItem) {
        const label = (item.objectCount === 1)
            ? translate('sulu_admin.object')
            : translate('sulu_admin.objects');

        return `${item.objectCount} ${label}`;
    }

    render() {
        const {
            data,
            onItemEditClick,
        } = this.props;

        return (
            <FolderList onFolderClick={onItemEditClick}>
                {data.map((item) => (
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
