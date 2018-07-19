// @flow
import React from 'react';
import {observer} from 'mobx-react';
import ColumnList from '../../../components/ColumnList';
import PublishIndicator from '../../../components/PublishIndicator';
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
        const {onItemActivation} = this.props;
        if (onItemActivation) {
            onItemActivation(id);
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

        const {activeItems, onAddClick} = this.props;

        if (onAddClick && activeItems && activeItems[index]) {
            onAddClick(activeItems[index]);
        }
    };

    render() {
        const {
            activeItems,
            disabledIds,
            loading,
            onAddClick,
            onItemClick,
            onItemSelectionChange,
            selections,
        } = this.props;

        const buttons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: onItemClick,
            });
        }

        if (onItemSelectionChange) {
            buttons.push({
                icon: 'su-check',
                onClick: this.handleItemSelectionChange,
            });
        }

        const toolbarItems = [];

        if (onAddClick) {
            toolbarItems.push({
                icon: 'su-plus-circle',
                type: 'button',
                onClick: this.handleColumnAdd,
            });
        }

        return (
            <div className={columnListAdapterStyles.columnListAdapter}>
                <ColumnList buttons={buttons} onItemClick={this.handleItemClick} toolbarItems={toolbarItems}>
                    {this.props.data.map((items, index) => (
                        <ColumnList.Column
                            key={index}
                            loading={index >= this.props.data.length - 1 && loading}
                        >
                            {items.map((item: Object) => (
                                // TODO: Don't access "hasChildren", "published", "publishedState" or "title" directly
                                <ColumnList.Item
                                    active={activeItems ? activeItems.includes(item.id) : undefined}
                                    disabled={disabledIds.includes(item.id)}
                                    hasChildren={item.hasChildren}
                                    id={item.id}
                                    key={item.id}
                                    selected={selections.includes(item.id)}
                                    indicators={[
                                        <PublishIndicator
                                            key={1}
                                            draft={item.publishedState === undefined ? false : !item.publishedState}
                                            published={item.publishedState === undefined ? false : item.published}
                                        />,
                                    ]}
                                >
                                    {item.title}
                                </ColumnList.Item>
                            ))}
                        </ColumnList.Column>
                    ))}
                </ColumnList>
            </div>
        );
    }
}
