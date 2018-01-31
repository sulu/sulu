// @flow
import React from 'react';
import type {Node} from 'react';
import {action, observable} from 'mobx';
import {arrayMove} from 'react-sortable-hoc';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import Icon from '../Icon';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';

type Props = {
    onChange: (value: *) => void,
    renderBlockContent: (value: *) => Node,
    value: Array<*>,
};

export default class BlockCollection extends React.Component<Props> {
    static defaultProps = {
        value: [],
    };

    @observable expandedBlocks: Array<boolean> = [];

    componentWillMount() {
        this.fillExpandedBlocksArray();
    }

    fillExpandedBlocksArray() {
        const {value} = this.props;
        const {expandedBlocks} = this;

        if (!value) {
            return;
        }

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(false));
    }

    @action handleAddBlock = () => {
        const {onChange, value} = this.props;

        if (value) {
            this.expandedBlocks.push(false);
            onChange([...value, {}]);
        }
    };

    @action handleRemove = (index: number) => {
        const {onChange, value} = this.props;

        if (value) {
            this.expandedBlocks.splice(index, 1);
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleSortEnd = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onChange, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));
    };

    @action handleCollapse = (index: number) => {
        const {expandedBlocks} = this;

        expandedBlocks[index] = false;
    };

    @action handleExpand = (index: number) => {
        const {expandedBlocks} = this;

        expandedBlocks[index] = true;
    };

    render() {
        const {renderBlockContent, value} = this.props;

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlockList
                    expandedBlocks={this.expandedBlocks}
                    lockAxis="y"
                    onExpand={this.handleExpand}
                    onCollapse={this.handleCollapse}
                    onRemove={this.handleRemove}
                    onSortEnd={this.handleSortEnd}
                    renderBlockContent={renderBlockContent}
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
