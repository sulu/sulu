// @flow
import React from 'react';
import type {ComponentType} from 'react';
import {SortableElement} from 'react-sortable-hoc';
import Block from '../Block';
import SortableHandle from './SortableHandle';
import type {RenderBlockContentCallback} from './types';

type Props = {
    activeType: string,
    expanded: boolean,
    onCollapse: (index: number) => void,
    onExpand: (index: number) => void,
    onRemove?: (index: number) => void,
    onTypeChange?: (type: string | number, index: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    sortIndex: number,
    types?: {[key: string]: string},
    value: Object,
};

class SortableBlock extends React.Component<Props> {
    handleCollapse = () => {
        const {sortIndex, onCollapse} = this.props;

        onCollapse(sortIndex);
    };

    handleExpand = () => {
        const {sortIndex, onExpand} = this.props;

        onExpand(sortIndex);
    };

    handleRemove = () => {
        const {sortIndex, onRemove} = this.props;

        if (onRemove) {
            onRemove(sortIndex);
        }
    };

    handleTypeChange = (type) => {
        const {sortIndex, onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, sortIndex);
        }
    };

    render() {
        const {activeType, expanded, onRemove, renderBlockContent, sortIndex, types, value} = this.props;

        return (
            <Block
                activeType={activeType}
                dragHandle={<SortableHandle />}
                expanded={expanded}
                onCollapse={this.handleCollapse}
                onExpand={this.handleExpand}
                onRemove={onRemove ? this.handleRemove : undefined}
                onTypeChange={this.handleTypeChange}
                types={types}
            >
                {renderBlockContent(value, activeType, sortIndex, expanded)}
            </Block>
        );
    }
}

const SortableElementBlock: ComponentType<Props> = SortableElement(SortableBlock);
export default SortableElementBlock;
