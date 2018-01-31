// @flow
import React from 'react';
import {SortableElement} from 'react-sortable-hoc';
import Block from '../Block';
import SortableHandle from './SortableHandle';

type Props = {
    expanded: boolean,
    onCollapse: (index: number) => void,
    onExpand: (index: number) => void,
    onRemove: (index: number) => void,
    sortIndex: number,
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

        onRemove(sortIndex);
    };

    render() {
        const {expanded, value} = this.props;

        return (
            <Block
                dragHandle={<SortableHandle />}
                expanded={expanded}
                onCollapse={this.handleCollapse}
                onExpand={this.handleExpand}
                onRemove={this.handleRemove}
            >
                {value.content}
            </Block>
        );
    }
}

export default SortableElement(SortableBlock);
