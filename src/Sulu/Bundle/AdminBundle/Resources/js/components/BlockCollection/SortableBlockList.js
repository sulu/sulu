// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';

type Props = {
    expandedBlocks: Array<number>,
    onExpand: (index: number) => void,
    onCollapse: (index: number) => void,
    onRemove: (index: number) => void,
    value: Array<*>,
};

@observer
class SortableBlockList extends React.Component<Props> {
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

    render() {
        const {expandedBlocks, value} = this.props;

        return (
            <div className={sortableBlockListStyles.sortableBlockList}>
                {value && value.map((block, index) => (
                    <SortableBlock
                        expanded={expandedBlocks.includes(index)}
                        index={index}
                        key={index}
                        onExpand={this.handleExpand}
                        onCollapse={this.handleCollapse}
                        onRemove={this.handleRemove}
                        sortIndex={index}
                        value={block}
                    />
                ))}
            </div>
        );
    }
}

export default SortableContainer(SortableBlockList);
