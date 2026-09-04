// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import SortableCollapsible from './SortableCollapsible';
import sortableCollapsibleListStyles from './sortableCollapsibleList.scss';
import type {
    CollapsibleActionConfig,
    CollapsibleConfig,
    CollapsibleMode,
    RenderCollapsibleContentCallback,
} from './types';

type Props<T: CollapsibleConfig> = {|
    actions: Array<CollapsibleActionConfig>,
    expandedCollapsibles: Array<boolean>,
    mode: CollapsibleMode,
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    renderCollapsibleContent: RenderCollapsibleContentCallback<T>,
    value: Array<T>,
|};

@observer
class SortableCollapsibleList<T: CollapsibleConfig> extends React.Component<Props<T>> {
    static defaultProps = {
        actions: [],
        mode: 'sortable',
    };

    handleCollapse = (index: number) => {
        const {onCollapse} = this.props;

        if (onCollapse) {
            onCollapse(index);
        }
    };

    handleExpand = (index: number) => {
        const {onExpand} = this.props;

        if (onExpand) {
            onExpand(index);
        }
    };

    render() {
        const {
            actions,
            expandedCollapsibles,
            mode,
            onCollapse,
            onExpand,
            renderCollapsibleContent,
            value,
        } = this.props;

        return (
            <div className={sortableCollapsibleListStyles.sortableCollapsibleList}>
                {value.map((collapsible, index) => (
                    <SortableCollapsible
                        actions={actions}
                        expanded={expandedCollapsibles[index]}
                        index={index}
                        key={index}
                        mode={mode}
                        onCollapse={onCollapse ? this.handleCollapse : undefined}
                        onExpand={onExpand ? this.handleExpand : undefined}
                        renderCollapsibleContent={renderCollapsibleContent}
                        sortIndex={index}
                        value={collapsible}
                    />
                ))}
            </div>
        );
    }
}

export default SortableContainer(SortableCollapsibleList);
