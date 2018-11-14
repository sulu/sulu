// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import classNames from 'classnames';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {|
    disabled: boolean,
    blockTypes: Array<string>,
    expandedBlocks: Array<boolean>,
    onExpand: (index: number) => void,
    onCollapse: (index: number) => void,
    onRemove: (index: number) => void,
    onTypeChange?: (type: string | number, index: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<BlockEntry>,
|};

@observer
class SortableBlockList extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

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
        const {disabled, expandedBlocks, onRemove, renderBlockContent, types, value} = this.props;

        const sortableBlockListClass = classNames(
            sortableBlockListStyles.sortableBlockList,
            {
                [sortableBlockListStyles.disabled]: disabled,
            }
        );

        return (
            <div className={sortableBlockListClass}>
                {value && value.map((block, index) => (
                    <SortableBlock
                        activeType={block.type}
                        expanded={!disabled && expandedBlocks[index]}
                        index={index}
                        key={block.__id}
                        onCollapse={this.handleCollapse}
                        onExpand={this.handleExpand}
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

export default SortableContainer(SortableBlockList);
