// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import type {AdapterProps} from '../types';

@observer
export default class FolderListAdapter extends React.Component<AdapterProps> {
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
                        info={item.objectCount}
                    />
                ))}
            </FolderList>
        );
    }
}
