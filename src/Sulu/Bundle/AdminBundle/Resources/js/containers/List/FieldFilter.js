// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import AbstractFieldFilterTypes from './fieldFilterTypes/AbstractFieldFilterType';
import FieldFilterItem from './FieldFilterItem';
import type {Schema} from './types';
import fieldFilterStyles from './fieldFilter.scss';

type Props = {|
    fields: Schema,
    onChange: ({[string]: mixed}) => void,
    value: {[string]: mixed},
|};

@observer
class FieldFilter extends React.Component<Props> {
    @observable fieldFilterTypes: {[column: string]: AbstractFieldFilterTypes<*>} = {};
    @observable filterOpen: boolean = false;
    @observable filterChipOpen: ?string = undefined;
    @observable value: {[string]: mixed} = {};

    @action componentDidMount() {
        const {value} = this.props;
        this.value = value;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {value} = this.props;
        if (prevProps.value !== value) {
            this.value = value;
        }
    }

    @computed get filteredFields(): Array<string> {
        return Object.keys(this.value);
    }

    @action handleFilterButtonClick = () => {
        this.filterOpen = true;
    };

    @action handleFilterClose = () => {
        this.filterOpen = false;
    };

    @action openFilterItem = (column: string) => {
        this.filterChipOpen = column;
    };

    @action closeFilterItem = () => {
        this.filterChipOpen = undefined;
    };

    handleFilterFieldClick = (column: string) => {
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

        onChange(
            Object.keys(value).reduce(function(newValue, currentColumn) {
                if (currentColumn !== column) {
                    newValue[currentColumn] = value[currentColumn];
                }

                return newValue;
            }, {})
        );
    };

    render() {
        const {fields} = this.props;

        return (
            <div className={fieldFilterStyles.fieldFilter}>
                {Object.keys(fields).length > 0 &&
                    <Fragment>
                        <ArrowMenu
                            anchorElement={
                                <div className={fieldFilterStyles.filterButton}>
                                    <Button
                                        icon="fa-filter"
                                        onClick={this.handleFilterButtonClick}
                                        showDropdownIcon={true}
                                        skin="icon"
                                    />
                                </div>
                            }
                            onClose={this.handleFilterClose}
                            open={this.filterOpen}
                        >
                            <ArrowMenu.Section>
                                {Object.keys(fields).map((fieldName) => (
                                    <ArrowMenu.Action
                                        disabled={this.filteredFields.includes(fieldName)}
                                        key={fieldName}
                                        onClick={this.handleFilterFieldClick}
                                        value={fieldName}
                                    >
                                        {fields[fieldName].label}
                                    </ArrowMenu.Action>
                                ))}
                            </ArrowMenu.Section>
                        </ArrowMenu>
                    </Fragment>
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
                        value={this.value[column]}
                    />
                ))}
            </div>
        );
    }
}

export default FieldFilter;
