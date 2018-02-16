// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {
    blockTypes: Array<string>,
    expandedBlocks: Array<boolean>,
    onExpand: (index: number) => void,
    onCollapse: (index: number) => void,
    onRemove: (index: number) => void,
    onTypeChange?: (type: string | number, index: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<BlockEntry>,
};

@observer
class SortableBlocks extends React.Component<Props> {
    handleExpand = (index: number) => {
        const {onExpand} = this.props;
        onExpand(index);
    };

    handleCollapse = (index: number) => {
        const {onCollapse} = this.props;
        onCollapse(index);
    };

    handleRemove = (index: number) => {
        const {onRemove} = this.props;
        onRemove(index);
    };

    handleTypeChange = (type: string | number, index: number) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, index);
        }
    };

    render() {
        const {expandedBlocks, onRemove, renderBlockContent, types, value} = this.props;

        return (
            <div className={sortableBlockListStyles.sortableBlockList}>
                {value && value.map((block, index) => (
                    <SortableBlock
                        activeType={block.type}
                        expanded={expandedBlocks[index]}
                        index={index}
                        key={block.__id}
                        onExpand={this.handleExpand}
                        onCollapse={this.handleCollapse}
                        onRemove={onRemove ? this.handleRemove : undefined}
                        onTypeChange={this.handleTypeChange}
                        renderBlockContent={renderBlockContent}
                        sortIndex={index}
                        types={types}
                        value={block}
                    />
                ))}
            </div>
        );
    }
}

export default SortableContainer(SortableBlocks);
