// @flow
import React from 'react';
import {action, comparer, computed, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove, translate} from '../../utils';
import Button from '../Button';
import Icon from '../Icon';
import SortableCollapsibleList from './SortableCollapsibleList';
import collapsibleCollectionStyles from './collapsibleCollection.scss';
import type {Node} from 'react';
import type {
    CollapsibleActionConfig,
    CollapsibleConfig,
    CollapsibleMode,
    RenderCollapsibleContentCallback,
} from './types';

type Props<T: CollapsibleConfig> = {|
    actions: Array<CollapsibleActionConfig>,
    addButtonText?: ?string,
    allExpanded: boolean,
    collapseAllText?: ?string,
    expandAllText?: ?string,
    movable: boolean,
    onAddClick?: () => void,
    onChange: (value: Array<T>) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    renderCollapsibleContent: RenderCollapsibleContentCallback<T>,
    toolbar?: Node,
    value: Array<T>,
|};

@observer
class CollapsibleCollection<T: CollapsibleConfig> extends React.Component<Props<T>> {
    static defaultProps = {
        actions: [],
        allExpanded: false,
        movable: true,
        value: [],
    };

    // Keyed by id, so a collapsible keeps its state when others before it are removed.
    expandedCollapsibles: Map<string, boolean> = observable.map();
    @observable mode: CollapsibleMode = 'sortable';

    keysDisposer: ?() => *;
    allExpandedDisposer: ?() => *;

    constructor(props: Props<T>) {
        super(props);

        this.keysDisposer = reaction(() => this.keys, this.syncExpandedCollapsibles, {
            equals: comparer.structural,
            fireImmediately: true,
        });
        this.allExpandedDisposer = reaction(() => this.props.allExpanded, this.setAllExpanded);

        if (props.movable === false) {
            this.mode = 'static';
        }
    }

    componentWillUnmount() {
        this.keysDisposer?.();
        this.allExpandedDisposer?.();
    }

    // Collapsibles without an id fall back to their position.
    getKey(collapsible: T, index: number): string {
        return collapsible.id !== undefined ? 'id-' + String(collapsible.id) : 'index-' + index;
    }

    @computed get keys(): Array<string> {
        return this.props.value.map((collapsible, index) => this.getKey(collapsible, index));
    }

    @computed get expandedStates(): Array<boolean> {
        return this.keys.map((key) => this.expandedCollapsibles.get(key) || false);
    }

    @action syncExpandedCollapsibles = (keys: Array<string>) => {
        const {expandedCollapsibles} = this;

        Array.from(expandedCollapsibles.keys())
            .filter((key) => !keys.includes(key))
            .forEach((key) => expandedCollapsibles.delete(key));

        keys.filter((key) => !expandedCollapsibles.has(key))
            .forEach((key) => expandedCollapsibles.set(key, this.props.allExpanded));
    };

    @action setAllExpanded = (expanded: boolean) => {
        this.keys.forEach((key) => this.expandedCollapsibles.set(key, expanded));
    };

    @action handleCollapse = (index: number) => {
        this.expandedCollapsibles.set(this.keys[index], false);
    };

    @action handleExpand = (index: number) => {
        this.expandedCollapsibles.set(this.keys[index], true);
    };

    handleClickCollapseAll = () => {
        this.setAllExpanded(false);
    };

    handleClickExpandAll = () => {
        this.setAllExpanded(true);
    };

    @action handleSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        const movedExpandedStates = arrayMove(this.expandedStates, oldIndex, newIndex);
        const movedValue = arrayMove(value, oldIndex, newIndex);

        movedValue.forEach((collapsible, index) => {
            this.expandedCollapsibles.set(this.getKey(collapsible, index), movedExpandedStates[index]);
        });
        onChange(movedValue);

        if (onSortEnd) {
            onSortEnd(oldIndex, newIndex);
        }
    };

    renderToggleButton = () => {
        const {collapseAllText, expandAllText} = this.props;
        const allCollapsed = this.expandedStates.every((expanded) => !expanded);

        return (
            <button
                className={collapsibleCollectionStyles.collapsibleCollectionActionButton}
                onClick={allCollapsed ? this.handleClickExpandAll : this.handleClickCollapseAll}
                type="button"
            >
                <Icon
                    aria-hidden={true}
                    className={collapsibleCollectionStyles.collapsibleCollectionActionButtonIcon}
                    name={allCollapsed ? 'su-expand-vertical' : 'su-collapse-vertical'}
                />
                <span className={collapsibleCollectionStyles.collapsibleCollectionActionButtonText}>
                    {allCollapsed
                        ? (expandAllText ? expandAllText : translate('sulu_admin.expand_all'))
                        : (collapseAllText ? collapseAllText : translate('sulu_admin.collapse_all'))
                    }
                </span>
            </button>
        );
    };

    render() {
        const {actions, addButtonText, onAddClick, renderCollapsibleContent, toolbar, value} = this.props;
        const showToggleButton = value.length > 1;

        return (
            <section className={collapsibleCollectionStyles.collapsibleCollection}>
                {(toolbar || showToggleButton) &&
                    <div className={collapsibleCollectionStyles.collapsibleCollectionActionButtonContainer}>
                        {toolbar && <div className={collapsibleCollectionStyles.toolbar}>{toolbar}</div>}
                        {showToggleButton && this.renderToggleButton()}
                    </div>
                }

                <div className={collapsibleCollectionStyles.spacer} />

                <SortableCollapsibleList
                    actions={actions}
                    expandedCollapsibles={this.expandedStates}
                    lockAxis="y"
                    mode={this.mode}
                    onCollapse={this.handleCollapse}
                    onExpand={this.handleExpand}
                    onSortEnd={this.handleSortEnd}
                    renderCollapsibleContent={renderCollapsibleContent}
                    useDragHandle={true}
                    value={value}
                />
                {onAddClick &&
                    <div className={collapsibleCollectionStyles.addButtonContainer}>
                        <Button icon="su-plus" onClick={onAddClick} skin="secondary">
                            {addButtonText ? addButtonText : translate('sulu_admin.add')}
                        </Button>
                    </div>
                }
            </section>
        );
    }
}

export default CollapsibleCollection;
