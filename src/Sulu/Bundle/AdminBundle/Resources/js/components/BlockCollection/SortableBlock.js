// @flow
import React from 'react';
import type {ComponentType} from 'react';
import {SortableElement} from 'react-sortable-hoc';
import Block from '../Block';
import SortableHandle from './SortableHandle';
import type {RenderBlockContentCallback} from './types';

type Props<T: string, U: {type: T}> = {
    activeType: T,
    expanded: boolean,
    icons?: Array<string>,
    movable?: boolean,
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    onRemove?: (index: number) => void,
    onSettingsClick?: (index: number) => void,
    onTypeChange?: (type: T, index: number) => void,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    sortIndex: number,
    types?: {[key: T]: string},
    value: Object,
};

class SortableBlock<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
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

    handleRemove = () => {
        const {sortIndex, onRemove} = this.props;

        if (onRemove) {
            onRemove(sortIndex);
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

    render() {
        const {
            activeType,
            expanded,
            icons,
            movable = true,
            onCollapse,
            onExpand,
            onRemove,
            onSettingsClick,
            renderBlockContent,
            sortIndex,
            types,
            value,
        } = this.props;

        return (
            <Block
                activeType={activeType}
                dragHandle={movable && <SortableHandle />}
                expanded={expanded}
                icons={icons}
                onCollapse={onCollapse ? this.handleCollapse : undefined}
                onExpand={onExpand ? this.handleExpand : undefined}
                onRemove={onRemove ? this.handleRemove : undefined}
                onSettingsClick={onSettingsClick && this.handleSettingsClick}
                onTypeChange={this.handleTypeChange}
                types={types}
            >
                {renderBlockContent(value, activeType, sortIndex, expanded)}
            </Block>
        );
    }
}

const SortableElementBlock: ComponentType<Props<*, *>> = SortableElement(SortableBlock);
export default SortableElementBlock;
