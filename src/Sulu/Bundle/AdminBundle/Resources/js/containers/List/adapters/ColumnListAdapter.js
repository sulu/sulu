// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import ColumnList from '../../../components/ColumnList';
import GhostIndicator from '../../../components/GhostIndicator';
import Icon from '../../../components/Icon';
import PublishIndicator from '../../../components/PublishIndicator';
import {translate} from '../../../utils/Translator';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import ColumnStructureStrategy from '../structureStrategies/ColumnStructureStrategy';
import AbstractAdapter from './AbstractAdapter';
import columnListAdapterStyles from './columnListAdapter.scss';

@observer
class ColumnListAdapter extends AbstractAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = ColumnStructureStrategy;

    static icon = 'su-columns';

    static searchable = false;

    static defaultProps = {
        data: [],
    };

    @observable orderColumn: ?number = undefined;

    @action handleItemClick = (id: string | number) => {
        const {data, onItemActivate} = this.props;

        // TODO: Don't access id directly but use some kind of metadata instead
        if (
            this.orderColumn !== undefined
            && this.orderColumn !== null
            && data[this.orderColumn].some((item) => item.id === id)
        ) {
            return;
        }

        if (onItemActivate) {
            onItemActivate(id);
            this.orderColumn = undefined;
        }
    };

    handleItemSelectionChange = (id: string | number) => {
        const {onItemSelectionChange, selections} = this.props;
        if (onItemSelectionChange) {
            onItemSelectionChange(id, !selections.includes(id));
        }
    };

    handleOrderChange = (id: string | number, order: number) => {
        const {data, onRequestItemOrder} = this.props;

        if (!onRequestItemOrder) {
            throw new Error(
                'Items were tried to order although there is no onRequestItemOrder callback available.'
                + ' This should not happen and is likely a bug.'
            );
        }

        if (this.orderColumn === undefined || this.orderColumn === null) {
            throw new Error(
                'Ordering can only be changed if a column has been selected to be ordered.'
                + ' This should not happen and is likely a bug.'
            );
        }

        const column = data[this.orderColumn];
        const itemsCount = column.length;
        if (order > itemsCount) {
            order = itemsCount;
        }

        return onRequestItemOrder(id, order).then(({ordered}) => ordered);
    };

    getIndicators = (item: Object) => {
        if (item.type && item.type.name === 'ghost') {
            return [<GhostIndicator key="ghost" locale={item.type.value} />];
        }

        const indicators = [];

        if (item.linked === 'internal') {
            indicators.push(<Icon key="internal" name="su-link2" />);
        } else if (item.linked === 'external') {
            indicators.push(<Icon key="external" name="su-link" />);
        } else if (item.type && item.type.name === 'shadow') {
            indicators.push(<Icon key="shadow" name="su-shadow-page" />);
        }

        const draft = item.publishedState === undefined ? false : !item.publishedState;
        const published = item.published === undefined ? false : !!item.published;

        if (draft || !published) {
            indicators.push(<PublishIndicator draft={draft} key="publish" published={published} />);
        }

        return indicators;
    };

    getButtons = (item: Object) => {
        const {onItemClick, onItemSelectionChange} = this.props;
        const isGhost = item.type && item.type.name === 'ghost';

        const buttons = [];
        if (onItemClick) {
            if (isGhost) {
                buttons.push({
                    icon: 'su-plus-circle',
                    onClick: onItemClick,
                });
            } else {
                buttons.push({
                    icon: 'su-pen',
                    onClick: onItemClick,
                });
            }
        }

        if (onItemSelectionChange) {
            const checkButton = {
                icon: 'su-check',
                onClick: this.handleItemSelectionChange,
            };
            buttons.push(checkButton);
        }

        return buttons;
    };

    getToolbarItems = (index: number) => {
        const {
            activeItems,
            adapterOptions: {
                display_root_level_toolbar: displayRootLevelToolbar = true,
            } = {},
            data,
            onItemAdd,
            onRequestItemCopy,
            onRequestItemDelete,
            onRequestItemMove,
            onRequestItemOrder,
        } = this.props;

        if (!activeItems) {
            throw new Error(
                'The ColumnListAdapter does not work without activeItems. '
                + 'This error should not happen and is likely a bug.'
            );
        }

        if (!displayRootLevelToolbar && !activeItems[index]) {
            return [];
        }

        if (this.orderColumn === index) {
            return [
                {
                    icon: 'su-times',
                    type: 'button',
                    onClick: action(() => {
                        this.orderColumn = undefined;
                    }),
                },
            ];
        }

        const toolbarItems = [];
        const parentColumn = data[index - 1];
        const parentItem = parentColumn ? parentColumn.find((item) => item.id === activeItems[index]) : undefined;
        const {
            _permissions: {
                add: parentAddPermission = true,
                edit: parentEditPermission = true,
            } = {},
        } = parentItem || {};

        if (onItemAdd && parentAddPermission) {
            toolbarItems.push({
                icon: 'su-plus-circle',
                type: 'button',
                onClick: () => {
                    onItemAdd(activeItems[index]);
                },
            });
        }

        const hasActiveItem = activeItems[index + 1] !== undefined;
        const column = data[index];
        const item = column ? column.find((item) => item.id === activeItems[index + 1]) : undefined;
        const {
            _permissions: {
                delete: deletePermission = true,
                edit: editPermission = true,
            } = {},
        } = item || {};

        const settingOptions = [];
        if (onRequestItemDelete) {
            settingOptions.push({
                disabled: !hasActiveItem || !deletePermission,
                label: translate('sulu_admin.delete'),
                onClick: () => {
                    const itemId = activeItems[index + 1];
                    if (!itemId) {
                        throw new Error(
                            'An undefined itemId cannot be deleted! This should not happen and is likely a bug.'
                        );
                    }

                    onRequestItemDelete(itemId);
                },
            });
        }

        if (onRequestItemMove) {
            settingOptions.push({
                disabled: !hasActiveItem || !editPermission,
                label: translate('sulu_admin.move'),
                onClick: () => {
                    const itemId = activeItems[index + 1];
                    if (!itemId) {
                        throw new Error(
                            'An undefined itemId cannot be deleted! This should not happen and is likely a bug.'
                        );
                    }

                    onRequestItemMove(itemId);
                },
            });
        }

        if (onRequestItemCopy) {
            settingOptions.push({
                disabled: !hasActiveItem || !editPermission,
                label: translate('sulu_admin.copy'),
                onClick: () => {
                    const itemId = activeItems[index + 1];
                    if (!itemId) {
                        throw new Error(
                            'An undefined itemId cannot be deleted! This should not happen and is likely a bug.'
                        );
                    }

                    onRequestItemCopy(itemId);
                },
            });
        }

        if (onRequestItemOrder) {
            settingOptions.push({
                disabled: !parentEditPermission,
                label: translate('sulu_admin.order'),
                onClick: action(() => {
                    this.orderColumn = index;
                }),
            });
        }

        if (settingOptions.length > 0) {
            toolbarItems.push({
                icon: 'su-cog',
                type: 'dropdown',
                options: settingOptions,
            });
        }

        return toolbarItems.length > 0 ? toolbarItems : undefined;
    };

    render() {
        const {
            activeItems,
            disabledIds,
            loading,
            selections,
        } = this.props;

        return (
            <div className={columnListAdapterStyles.columnListAdapter}>
                <ColumnList onItemClick={this.handleItemClick} toolbarItemsProvider={this.getToolbarItems}>
                    {this.props.data.map((items, index) => (
                        <ColumnList.Column
                            key={index}
                            loading={index >= this.props.data.length - 1 && loading}
                        >
                            {items.map((item: Object, itemIndex: number) => (
                                // TODO: Don't access hasChildren, published, publishedState, title or type directly
                                <ColumnList.Item
                                    active={activeItems ? activeItems.includes(item.id) : undefined}
                                    buttons={this.getButtons(item)}
                                    disabled={disabledIds.includes(item.id)}
                                    hasChildren={item.hasChildren}
                                    id={item.id}
                                    indicators={this.getIndicators(item)}
                                    key={item.id}
                                    onOrderChange={this.handleOrderChange}
                                    order={itemIndex + 1}
                                    selected={selections.includes(item.id)}
                                    showOrderField={this.orderColumn === index}
                                >
                                    {item.title || item.name}
                                </ColumnList.Item>
                            ))}
                        </ColumnList.Column>
                    ))}
                </ColumnList>
            </div>
        );
    }
}

export default ColumnListAdapter;
