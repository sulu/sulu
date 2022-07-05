// @flow
import React from 'react';
import {SortableElement} from 'react-sortable-hoc';
import log from 'loglevel';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import Block from '../Block';
import {translate} from '../../utils';
import SortableHandle from './SortableHandle';
import SelectionHandle from './SelectionHandle';
import type {ActionConfig} from '../Block/types';
import type {ComponentType} from 'react';
import type {BlockActionConfig, BlockMode, RenderBlockContentCallback} from './types';

type Props<T: string, U: {type: T}> = {
    actions: Array<BlockActionConfig>,
    activeType: T,
    expanded: boolean,
    icons?: Array<string>,
    mode?: BlockMode,
    movable?: boolean, // @deprecated
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    onRemove?: (index: number) => void, // @deprecated
    onSelect?: (index: number, selected: boolean) => void,
    onSettingsClick?: (index: number) => void,
    onTypeChange?: (type: T, index: number) => void,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    selected: boolean,
    sortIndex: number,
    types?: {[key: T]: string},
    value: Object,
};

@observer
class SortableBlock<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
    static defaultProps = {
        actions: [],
        mode: 'sortable',
        movable: true,
        selected: false,
    };

    @computed get actions(): Array<ActionConfig> {
        const {onRemove, actions, sortIndex} = this.props;

        const wrappedActions: Array<ActionConfig> = actions.map((action) => {
            if (action.type !== 'divider') {
                return {
                    ...action,
                    onClick: () => action.onClick(sortIndex),
                };
            }

            return action;
        });

        // @deprecated
        if (onRemove) {
            log.warn(
                'The "onRemove" prop of the "SortableBlock" component is deprecated since 2.5 and will ' +
                'be removed. Use the "actions" prop with an appropriate callback instead.'
            );

            return [
                ...wrappedActions,
                {
                    type: 'button',
                    icon: 'su-trash-alt',
                    label: translate('sulu_admin.delete'),
                    onClick: () => onRemove(sortIndex),
                },
            ];
        }

        return wrappedActions;
    }

    handleCollapse = () => {
        const {sortIndex, onCollapse} = this.props;

        if (onCollapse) {
            onCollapse(sortIndex);
        }
    };

    handleExpand = () => {
        const {sortIndex, onExpand} = this.props;

        if (onExpand) {
            onExpand(sortIndex);
        }
    };

    handleSelect = () => {
        const {sortIndex, onSelect, selected} = this.props;

        if (onSelect) {
            onSelect(sortIndex, !selected);
        }
    };

    handleSettingsClick = () => {
        const {sortIndex, onSettingsClick} = this.props;

        if (onSettingsClick) {
            onSettingsClick(sortIndex);
        }
    };

    handleTypeChange: (type: T) => void = (type) => {
        const {sortIndex, onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, sortIndex);
        }
    };

    renderHandle = () => {
        const {mode, movable, selected} = this.props;

        if (mode === 'sortable' && movable !== false) {
            return <SortableHandle />;
        }

        if (mode === 'selection') {
            return <SelectionHandle checked={selected} onChange={this.handleSelect} />;
        }

        return null;
    };

    render() {
        const {
            activeType,
            expanded,
            icons,
            onCollapse,
            onExpand,
            onSettingsClick,
            renderBlockContent,
            selected,
            sortIndex,
            types,
            value,
        } = this.props;

        return (
            <Block
                actions={this.actions}
                activeType={activeType}
                expanded={expanded}
                handle={this.renderHandle()}
                icons={icons}
                onCollapse={onCollapse ? this.handleCollapse : undefined}
                onExpand={onExpand ? this.handleExpand : undefined}
                onSettingsClick={onSettingsClick && this.handleSettingsClick}
                onTypeChange={this.handleTypeChange}
                selected={selected}
                types={types}
            >
                {renderBlockContent(value, activeType, sortIndex, expanded)}
            </Block>
        );
    }
}

const SortableElementBlock: ComponentType<Props<*, *>> = SortableElement(SortableBlock);
export default SortableElementBlock;
