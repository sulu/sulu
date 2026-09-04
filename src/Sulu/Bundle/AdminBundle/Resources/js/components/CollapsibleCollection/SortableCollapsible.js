// @flow
import React from 'react';
import {SortableElement} from 'react-sortable-hoc';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import Collapsible from '../Collapsible';
import SortableHandle from './SortableHandle';
import type {ActionConfig} from '../Collapsible/types';
import type {ComponentType} from 'react';
import type {
    CollapsibleActionConfig,
    CollapsibleConfig,
    CollapsibleMode,
    RenderCollapsibleContentCallback,
} from './types';

type Props<T: CollapsibleConfig> = {
    actions: Array<CollapsibleActionConfig>,
    expanded: boolean,
    mode: CollapsibleMode,
    onCollapse?: (index: number) => void,
    onExpand?: (index: number) => void,
    renderCollapsibleContent: RenderCollapsibleContentCallback<T>,
    sortIndex: number,
    value: T,
};

@observer
class SortableCollapsible<T: CollapsibleConfig> extends React.Component<Props<T>> {
    static defaultProps = {
        actions: [],
        expanded: false,
        mode: 'sortable',
    };

    @computed get actions(): Array<ActionConfig> {
        const {actions, sortIndex} = this.props;

        return actions.map((action) => ({
            ...action,
            onClick: () => action.onClick(sortIndex),
        }));
    }

    handleCollapse = () => {
        const {onCollapse, sortIndex} = this.props;

        if (onCollapse) {
            onCollapse(sortIndex);
        }
    };

    handleExpand = () => {
        const {onExpand, sortIndex} = this.props;

        if (onExpand) {
            onExpand(sortIndex);
        }
    };

    renderHandle = () => {
        const {mode} = this.props;

        if (mode === 'sortable') {
            return <SortableHandle />;
        }

        return null;
    };

    render() {
        const {expanded, onCollapse, onExpand, renderCollapsibleContent, sortIndex, value} = this.props;

        return (
            <Collapsible
                actions={this.actions}
                expanded={expanded}
                handle={this.renderHandle()}
                onCollapse={onCollapse ? this.handleCollapse : undefined}
                onExpand={onExpand ? this.handleExpand : undefined}
                subtitle={value.subtitle}
                title={value.title}
            >
                {renderCollapsibleContent(value, sortIndex, expanded)}
            </Collapsible>
        );
    }
}

const SortableElementCollapsible: ComponentType<Props<*>> = SortableElement(SortableCollapsible);
export default SortableElementCollapsible;
