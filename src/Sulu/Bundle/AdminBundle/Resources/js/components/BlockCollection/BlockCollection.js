// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {arrayMove} from 'react-sortable-hoc';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import Icon from '../Icon';
import type {FieldTypeProps} from '../../types';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';

export default class BlockCollection extends React.Component<FieldTypeProps<Array<Object>>> {
    static defaultProps = {
        value: [],
    };

    @observable expandedBlocks = [];

    handleAddBlock = () => {
        const {onChange, value} = this.props;

        if (value) {
            onChange([...value, {content: 'Test content'}]);
        }
    };

    handleSortEnd = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onChange, value} = this.props;

        onChange(arrayMove(value, oldIndex, newIndex));
    };

    @action handleCollapse = (index: number) => {
        const {expandedBlocks} = this;

        if (!expandedBlocks.includes(index)) {
            return;
        }

        expandedBlocks.splice(expandedBlocks.indexOf(index), 1);
    };

    @action handleExpand = (index: number) => {
        const {expandedBlocks} = this;

        if (expandedBlocks.includes(index)) {
            return;
        }

        expandedBlocks.push(index);
    };

    render() {
        const {value} = this.props;

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlockList
                    expandedBlocks={this.expandedBlocks}
                    lockAxis="y"
                    onExpand={this.handleExpand}
                    onCollapse={this.handleCollapse}
                    onSortEnd={this.handleSortEnd}
                    useDragHandle={true}
                    value={value}
                />
                <Button skin="secondary" onClick={this.handleAddBlock}>
                    <Icon name="plus" className={blockCollectionStyles.addButtonIcon} />
                    {translate('sulu_admin.add_block')}
                </Button>
            </section>
        );
    }
}
