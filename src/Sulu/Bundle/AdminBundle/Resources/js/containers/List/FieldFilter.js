// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import Chip from '../../components/Chip';
import type {Schema} from './types';

type Props = {|
    fields: Schema,
    onChange: ({[string]: mixed}) => void,
    value: {[string]: mixed},
|};

@observer
class FieldFilter extends React.Component<Props> {
    @observable filterOpen: boolean = false;
    @observable filterChipOpen: ?string = undefined;

    @computed get filteredFields(): Array<string> {
        const {value} = this.props;

        return Object.keys(value);
    }

    @action handleFilterButtonClick = () => {
        this.filterOpen = true;
    };

    @action handleFilterClose = () => {
        this.filterOpen = false;
    };

    @action openFilterChip = (column: string) => {
        this.filterChipOpen = column;
    };

    @action closeFilterChip = () => {
        this.filterChipOpen = undefined;
    };

    handleFilterFieldClick = (column: string) => {
        const {onChange, value} = this.props;
        onChange({...value, [column]: undefined});
        this.openFilterChip(column);
    };

    handleFilterChipClick = (column: string) => {
        this.openFilterChip(column);
    };

    handleFilterChipClose = () => {
        this.closeFilterChip();
    };

    @action handleFilterChipDelete = (column: string) => {
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
            <Fragment>
                <ArrowMenu
                    anchorElement={
                        <div>
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
                {this.filteredFields.map((column) => (
                    <ArrowMenu
                        anchorElement={
                            <span>
                                <Chip
                                    onClick={this.handleFilterChipClick}
                                    onDelete={this.handleFilterChipDelete}
                                    value={column}
                                >
                                    {fields[column].label}
                                </Chip>
                            </span>
                        }
                        key={column}
                        onClose={this.handleFilterChipClose}
                        open={this.filterChipOpen === column}
                    >
                        <ArrowMenu.Section>
                            <p>{fields[column].filterType}</p>
                        </ArrowMenu.Section>
                    </ArrowMenu>
                ))}
            </Fragment>
        );
    }
}

export default FieldFilter;
