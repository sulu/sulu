// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import classNames from 'classnames';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {BlockActionConfig, RenderBlockContentCallback} from './types';
import type {Node} from 'react';

type Props<T: string, U: {type: T}> = {|
    blockActions: Array<BlockActionConfig>,
    disabled: boolean,
    expandedBlocks: Array<boolean>,
    generatedBlockIds: Array<number>,
    icons?: Array<Array<string>>,
    movable: boolean,
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    onSettingsClick?: (index: number) => void,
    onTypeChange?: (type: T, index: number) => void,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    renderDivider?: (aboveBlockIndex: number) => Node,
    types?: {[key: T]: string},
    value: Array<U>,
|};

@observer
class SortableBlockList<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
    static defaultProps = {
        blockActions: [],
        disabled: false,
        movable: true,
    };

    handleExpand = (index: number) => {
        const {onExpand} = this.props;
        if (onExpand) {
            onExpand(index);
        }
    };

    handleCollapse = (index: number) => {
        const {onCollapse} = this.props;
        if (onCollapse) {
            onCollapse(index);
        }
    };

    handleSettingsClick = (index: number) => {
        const {onSettingsClick} = this.props;

        if (onSettingsClick) {
            onSettingsClick(index);
        }
    };

    handleTypeChange: (type: T, index: number) => void = (type, index) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, index);
        }
    };

    render() {
        const {
            blockActions,
            disabled,
            expandedBlocks,
            generatedBlockIds,
            icons,
            movable,
            onCollapse,
            onExpand,
            onSettingsClick,
            renderBlockContent,
            renderDivider,
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
                    <Fragment key={index}>
                        <SortableBlock
                            actions={blockActions}
                            activeType={block.type}
                            expanded={!disabled && expandedBlocks[index]}
                            icons={icons && icons[index]}
                            index={index}
                            key={generatedBlockIds[index]}
                            movable={movable}
                            onCollapse={onCollapse ? this.handleCollapse : undefined}
                            onExpand={onExpand ? this.handleExpand : undefined}
                            onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                            onTypeChange={this.handleTypeChange}
                            renderBlockContent={renderBlockContent}
                            sortIndex={index}
                            types={types}
                            value={block}
                        />
                        {renderDivider && index < value.length - 1 && (
                            renderDivider(index)
                        )}
                    </Fragment>
                ))}
            </div>
        );
    }
}

export default SortableContainer(SortableBlockList);
