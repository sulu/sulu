// @flow
import React from 'react';
import {observer} from 'mobx-react';
import ColumnList from '../../../components/ColumnList';
import GhostIndicator from '../../../components/GhostIndicator';
import PublishIndicator from '../../../components/PublishIndicator';
import {translate} from '../../../utils/Translator';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import ColumnStructureStrategy from '../structureStrategies/ColumnStructureStrategy';
import AbstractAdapter from './AbstractAdapter';
import columnListAdapterStyles from './columnListAdapter.scss';

@observer
export default class ColumnListAdapter extends AbstractAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = ColumnStructureStrategy;

    static icon = 'su-columns';

    static defaultProps = {
        data: [],
    };

    handleItemClick = (id: string | number) => {
        const {onItemActivate} = this.props;
        if (onItemActivate) {
            onItemActivate(id);
        }
    };

    handleItemSelectionChange = (id: string | number) => {
        const {onItemSelectionChange, selections} = this.props;
        if (onItemSelectionChange) {
            onItemSelectionChange(id, !selections.includes(id));
        }
    };

    handleColumnAdd = (index?: string | number) => {
        if (!index || typeof index !== 'number') {
            return;
        }

        const {activeItems, onItemAdd} = this.props;

        if (onItemAdd && activeItems && activeItems[index]) {
            onItemAdd(activeItems[index]);
        }
    };

    render() {
        const {
            activeItems,
            disabledIds,
            loading,
            onItemAdd,
            onRequestItemCopy,
            onRequestItemDelete,
            onItemClick,
            onItemSelectionChange,
            onRequestItemMove,
            selections,
        } = this.props;

        const buttons = [];
        const ghostButtons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: onItemClick,
            });
            ghostButtons.push({
                icon: 'su-plus-circle',
                onClick: onItemClick,
            });
        }

        if (onItemSelectionChange) {
            const checkButton = {
                icon: 'su-check',
                onClick: this.handleItemSelectionChange,
            };
            buttons.push(checkButton);
            ghostButtons.push(checkButton);
        }

        const toolbarItems = [];

        if (onItemAdd) {
            toolbarItems.push({
                icon: 'su-plus-circle',
                type: 'button',
                onClick: this.handleColumnAdd,
            });
        }

        if (!activeItems) {
            throw new Error(
                'The ColumnListAdapter does not work without activeItems. '
                + 'This error should not happen and is likely a bug.'
            );
        }

        const isDisabled = (index) => {
            return activeItems[((index + 1: any): number)] === undefined;
        };

        const settingOptions = [];
        if (onRequestItemDelete) {
            settingOptions.push({
                isDisabled,
                label: translate('sulu_admin.delete'),
                onClick: (index) => {
                    onRequestItemDelete(activeItems[index + 1]);
                },
            });
        }

        if (onRequestItemMove) {
            settingOptions.push({
                isDisabled,
                label: translate('sulu_admin.move'),
                onClick: (index) => {
                    onRequestItemMove(activeItems[index + 1]);
                },
            });
        }

        if (onRequestItemCopy) {
            settingOptions.push({
                isDisabled,
                label: translate('sulu_admin.copy'),
                onClick: (index) => {
                    onRequestItemCopy(activeItems[index + 1]);
                },
            });
        }

        if (settingOptions.length > 0) {
            toolbarItems.push({
                icon: 'su-cog',
                type: 'dropdown',
                options: settingOptions,
            });
        }

        return (
            <div className={columnListAdapterStyles.columnListAdapter}>
                <ColumnList onItemClick={this.handleItemClick} toolbarItems={toolbarItems}>
                    {this.props.data.map((items, index) => (
                        <ColumnList.Column
                            key={index}
                            loading={index >= this.props.data.length - 1 && loading}
                        >
                            {items.map((item: Object) => {
                                const draft = item.publishedState === undefined ? false : !item.publishedState;
                                const published = item.published === undefined ? false : !!item.published;

                                return (
                                    // TODO: Don't access hasChildren, published, publishedState, title or type directly
                                    <ColumnList.Item
                                        active={activeItems ? activeItems.includes(item.id) : undefined}
                                        buttons={item.type && item.type.name === 'ghost' ? ghostButtons : buttons}
                                        disabled={disabledIds.includes(item.id)}
                                        hasChildren={item.hasChildren}
                                        id={item.id}
                                        indicators={item.type && item.type.name === 'ghost'
                                            ? [
                                                <GhostIndicator key={'ghost'} locale={item.type.value} />,
                                            ]
                                            : [
                                                (draft || !published) && <PublishIndicator
                                                    key={'publish'}
                                                    draft={draft}
                                                    published={published}
                                                />,
                                            ]
                                        }
                                        key={item.id}
                                        selected={selections.includes(item.id)}
                                    >
                                        {item.title}
                                    </ColumnList.Item>
                                );})
                            }
                        </ColumnList.Column>
                    ))}
                </ColumnList>
            </div>
        );
    }
}
