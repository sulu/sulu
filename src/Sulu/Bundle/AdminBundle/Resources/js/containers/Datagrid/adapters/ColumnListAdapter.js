// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {LoadingStrategyInterface, StructureStrategyInterface} from '../types';
import ColumnList from '../../../components/ColumnList';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class ColumnListAdapter extends AbstractAdapter {
    static getLoadingStrategy(): LoadingStrategyInterface {
        return new FullLoadingStrategy();
    }

    static getStructureStrategy(): StructureStrategyInterface {
        return new FlatStructureStrategy();
    }

    static defaultProps = {
        data: [],
    };

    handleItemClick = () => {};

    render() {
        const {data} = this.props;

        return (
            <ColumnList onItemClick={this.handleItemClick} toolbarItems={[]}>
                <ColumnList.Column>
                    {data.map((item: Object) => (
                        // TODO: Don't access properties like "hasSub" or "title" directly.
                        <ColumnList.Item id={item.id} key={item.id} hasChildren={item.hasSub}>
                            {item.title}
                        </ColumnList.Item>
                    ))}
                </ColumnList.Column>
            </ColumnList>
        );
    }
}
