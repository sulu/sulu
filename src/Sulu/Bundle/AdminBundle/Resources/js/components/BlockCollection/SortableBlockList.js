// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import classNames from 'classnames';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {|
    blockTypes: Array<string>,
    disabled: boolean,
    expandedBlocks: Array<boolean>,
    generatedBlockIds: Array<number>,
    icons?: Array<Array<string>>,
    movable: boolean,
    onCollapse: (index: number) => void,
    onExpand: (index: number) => void,
    onRemove?: (index: number) => void,
    onSettingsClick?: (index: number) => void,
    onTypeChange?: (type: string | number, index: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<BlockEntry>,
|};

@observer
class SortableBlockList extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        movable: true,
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

        if (onRemove) {
            onRemove(index);
        }
    };

    handleSettingsClick = (index: number) => {
        const {onSettingsClick} = this.props;

        if (onSettingsClick) {
            onSettingsClick(index);
        }
    };

    handleTypeChange = (type: string | number, index: number) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, index);
        }
    };

    render() {
        const {
            disabled,
            expandedBlocks,
            generatedBlockIds,
            icons,
            movable,
            onRemove,
            onSettingsClick,
            renderBlockContent,
            types,
            value,
        } = this.props;

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
                        icons={icons && icons[index]}
                        index={index}
                        key={generatedBlockIds[index]}
                        movable={movable}
                        onCollapse={this.handleCollapse}
                        onExpand={this.handleExpand}
                        onRemove={onRemove ? this.handleRemove : undefined}
                        onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
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
