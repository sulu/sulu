// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import {translate} from '../../../utils/Translator';
import PaginationStrategy from '../loadingStrategies/PaginationStrategy';
import FlatStrategy from '../structureStrategies/FlatStrategy';
import type {LoadingStrategyInterface, StructureStrategyInterface} from '../types';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class FolderAdapter extends AbstractAdapter {
    static getLoadingStrategy(): LoadingStrategyInterface {
        return new PaginationStrategy();
    }

    static getStructureStrategy(): StructureStrategyInterface {
        return new FlatStrategy();
    }

    static defaultProps = {
        data: [],
    };

    static getInfoText(item: Object) {
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
                    // TODO: Don't access properties like "title" directly.
                    <FolderList.Folder
                        key={item.id}
                        id={item.id}
                        title={item.title}
                        info={FolderAdapter.getInfoText(item)}
                    />
                ))}
            </FolderList>
        );
    }
}
