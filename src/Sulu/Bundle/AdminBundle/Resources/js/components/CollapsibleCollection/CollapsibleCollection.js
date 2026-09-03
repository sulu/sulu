// @flow
import React from 'react';
import {action, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove, translate} from '../../utils';
import Button from '../Button';
import Icon from '../Icon';
import SortableCollapsibleList from './SortableCollapsibleList';
import collapsibleCollectionStyles from './collapsibleCollection.scss';
import type {
    CollapsibleActionConfig,
    CollapsibleConfig,
    CollapsibleMode,
    RenderCollapsibleContentCallback,
} from './types';

type Props<T: CollapsibleConfig> = {|
    actions: Array<CollapsibleActionConfig>,
    addButtonText?: ?string,
    collapseAllText?: ?string,
    expandAllText?: ?string,
    movable: boolean,
    onAddClick?: () => void,
    onChange: (value: Array<T>) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    renderCollapsibleContent: RenderCollapsibleContentCallback<T>,
    value: Array<T>,
|};

@observer
class CollapsibleCollection<T: CollapsibleConfig> extends React.Component<Props<T>> {
    static defaultProps = {
        actions: [],
        movable: true,
        value: [],
    };

    @observable expandedCollapsibles: Array<boolean> = [];
    @observable mode: CollapsibleMode = 'sortable';

    fillArraysDisposer: ?() => *;

    constructor(props: Props<T>) {
        super(props);

        this.fillArraysDisposer = reaction(() => this.props.value.length, this.fillArrays, {fireImmediately: true});

        if (props.movable === false) {
            this.mode = 'static';
        }
    }

    componentWillUnmount() {
        this.fillArraysDisposer?.();
    }

    @action fillArrays = () => {
        const {value} = this.props;
        const {expandedCollapsibles} = this;

        if (expandedCollapsibles.length > value.length) {
            expandedCollapsibles.splice(value.length);
        }

        // A collapsible is expanded when it enters the collection.
        expandedCollapsibles.push(...new Array(value.length - expandedCollapsibles.length).fill(true));
    };

    @action handleCollapse = (index: number) => {
        this.expandedCollapsibles[index] = false;
    };

    @action handleExpand = (index: number) => {
        this.expandedCollapsibles[index] = true;
    };

    @action handleClickCollapseAll = () => {
        this.expandedCollapsibles.forEach((expanded, index) => {
            this.expandedCollapsibles[index] = false;
        });
    };

    @action handleClickExpandAll = () => {
        this.expandedCollapsibles.forEach((expanded, index) => {
            this.expandedCollapsibles[index] = true;
        });
    };

    @action handleSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        this.expandedCollapsibles = arrayMove(this.expandedCollapsibles, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));

        if (onSortEnd) {
            onSortEnd(oldIndex, newIndex);
        }
    };

    renderToggleButton = () => {
        const {collapseAllText, expandAllText} = this.props;
        const allCollapsed = this.expandedCollapsibles.every((expanded) => !expanded);

        return (
            <div className={collapsibleCollectionStyles.collapsibleCollectionActionButtonContainer}>
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
            </div>
        );
    };

    render() {
        const {actions, addButtonText, onAddClick, renderCollapsibleContent, value} = this.props;

        return (
            <section className={collapsibleCollectionStyles.collapsibleCollection}>
                {value.length > 1 && this.renderToggleButton()}

                <div className={collapsibleCollectionStyles.spacer} />

                <SortableCollapsibleList
                    actions={actions}
                    expandedCollapsibles={this.expandedCollapsibles}
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
