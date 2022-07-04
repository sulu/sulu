// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import classNames from 'classnames';
import log from 'loglevel';
import {computed} from 'mobx';
import {translate} from '../../utils';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {BlockActionConfig, BlockMode, RenderBlockContentCallback} from './types';
import type {Node} from 'react';

type Props<T: string, U: {type: T}> = {|
    blockActions: Array<BlockActionConfig>,
    disabled: boolean,
    expandedBlocks: Array<boolean>,
    generatedBlockIds: Array<number>,
    icons?: Array<Array<string>>,
    mode?: BlockMode,
    movable?: boolean, // @deprecated
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    onRemove?: (index: number) => void, // @deprecated
    onSelect?: (index: number, selected: boolean) => void,
    onSettingsClick?: (index: number) => void,
    onTypeChange?: (type: T, index: number) => void,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    renderDivider?: (aboveBlockIndex: number) => Node,
    selectedBlocks: Array<boolean>,
    types?: {[key: T]: string},
    value: Array<U>,
|};

@observer
class SortableBlockList<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
    static defaultProps = {
        blockActions: [],
        disabled: false,
        mode: 'sortable',
        movable: null,
    };

    constructor(props: Props<T, U>) {
        super(props);

        if (props.movable === false) {
            log.warn(
                'The "movable" prop of the "SortableBlockList" component is deprecated since 2.5 and will ' +
                'be removed. Use the "blockMode" prop with "static" or "sortable" instead.'
            );
        }
    }

    @computed get blockActions(): Array<BlockActionConfig> {
        const {onRemove, blockActions} = this.props;

        // @deprecated
        if (onRemove) {
            log.warn(
                'The "onRemove" prop of the "SortableBlockList" component is deprecated since 2.5 and will ' +
                'be removed. Use the "blockActions" prop with an appropriate callback instead.'
            );

            return [
                ...blockActions,
                {
                    type: 'button',
                    icon: 'su-trash-alt',
                    label: translate('sulu_admin.delete'),
                    // $FlowFixMe
                    onClick: onRemove,
                },
            ];
        }

        return blockActions;
    }

    handleExpand = (index: number) => {
        const {onExpand} = this.props;
        if (onExpand) {
            onExpand(index);
        }
    };

    handleSelect = (index: number, selected: boolean) => {
        const {onSelect} = this.props;
        if (onSelect) {
            onSelect(index, selected);
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
            disabled,
            expandedBlocks,
            generatedBlockIds,
            icons,
            mode,
            movable,
            onCollapse,
            onExpand,
            onSelect,
            onSettingsClick,
            renderBlockContent,
            renderDivider,
            selectedBlocks,
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
                            actions={this.blockActions}
                            activeType={block.type}
                            expanded={!disabled && expandedBlocks[index]}
                            icons={icons && icons[index]}
                            index={index}
                            key={generatedBlockIds[index]}
                            mode={(mode === 'sortable' && movable !== false) ? 'sortable' : mode}
                            onCollapse={onCollapse ? this.handleCollapse : undefined}
                            onExpand={onExpand ? this.handleExpand : undefined}
                            onSelect={onSelect ? this.handleSelect : undefined}
                            onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                            onTypeChange={this.handleTypeChange}
                            renderBlockContent={renderBlockContent}
                            selected={selectedBlocks[index]}
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
