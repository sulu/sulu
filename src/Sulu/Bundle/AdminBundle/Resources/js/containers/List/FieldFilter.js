// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import FieldFilterItem from './FieldFilterItem';
import fieldFilterStyles from './fieldFilter.scss';
import type {Schema} from './types';

type Props = {|
    fields: Schema,
    onChange: ({[string]: mixed}) => void,
    value: {[string]: mixed},
|};

@observer
class FieldFilter extends React.Component<Props> {
    @observable filterMenuOpen: boolean = false;
    @observable filterChipOpen: ?string = undefined;

    @computed get filteredFields(): Array<string> {
        return Object.keys(this.props.value);
    }

    @action handleFilterMenuButtonClick = () => {
        this.filterMenuOpen = true;
    };

    @action handleFilterMenuClose = () => {
        this.filterMenuOpen = false;
    };

    @action openFilterItem = (column: string) => {
        this.filterChipOpen = column;
    };

    @action closeFilterItem = () => {
        this.filterChipOpen = undefined;
    };

    handleFilterMenuActionClick = (column: string) => {
        const {onChange, value} = this.props;

        onChange({...value, [column]: undefined});
        this.openFilterItem(column);
    };

    handleFilterItemClick = (column: string) => {
        this.openFilterItem(column);
    };

    handleFilterItemClose = () => {
        this.closeFilterItem();
    };

    handleFilterItemChange = (column: string, columnValue: mixed) => {
        const {onChange, value} = this.props;
        onChange({...value, [column]: columnValue});
        this.closeFilterItem();
    };

    @action handleFilterItemDelete = (column: string) => {
        const {onChange, value} = this.props;

        const {[column]: deletedFilter, ...newValue} = value;

        onChange(newValue);
    };

    render() {
        const {fields, value} = this.props;

        return (
            <div className={fieldFilterStyles.fieldFilter}>
                {Object.keys(fields).length > 0 &&
                    <ArrowMenu
                        anchorElement={
                            <div className={fieldFilterStyles.filterButton}>
                                <Button
                                    icon="su-filter"
                                    onClick={this.handleFilterMenuButtonClick}
                                    showDropdownIcon={true}
                                    skin="icon"
                                />
                            </div>
                        }
                        onClose={this.handleFilterMenuClose}
                        open={this.filterMenuOpen}
                    >
                        <ArrowMenu.Section>
                            {Object.keys(fields).map((column) => (
                                <ArrowMenu.Action
                                    disabled={this.filteredFields.includes(column)}
                                    key={column}
                                    onClick={this.handleFilterMenuActionClick}
                                    value={column}
                                >
                                    {fields[column].label}
                                </ArrowMenu.Action>
                            ))}
                        </ArrowMenu.Section>
                    </ArrowMenu>
                }
                {this.filteredFields.map((column) => (
                    <FieldFilterItem
                        column={column}
                        filterType={fields[column].filterType}
                        filterTypeParameters={fields[column].filterTypeParameters}
                        key={column}
                        label={fields[column].label}
                        onChange={this.handleFilterItemChange}
                        onClick={this.handleFilterItemClick}
                        onClose={this.handleFilterItemClose}
                        onDelete={this.handleFilterItemDelete}
                        open={this.filterChipOpen === column}
                        value={value[column]}
                    />
                ))}
            </div>
        );
    }
}

export default FieldFilter;
