// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import classNames from 'classnames';
import ArrowMenu from '../ArrowMenu';
import Grid from '../Grid';
import Icon from '../Icon';
import fieldStyles from './field.scss';
import gridStyles from './grid.scss';
import type {ColSpan} from '../Grid';
import type {Node} from 'react';
import type {FormFieldTypes} from './types';

type Props<T: string | number> = {|
    children: Node,
    colSpan: ColSpan,
    description?: string,
    error?: string,
    id?: string,
    label?: string,
    onTypeChange?: (type: T) => void,
    required: boolean,
    skin?: 'light' | 'dark',
    spaceAfter: ColSpan,
    type?: T,
    types?: FormFieldTypes,
|};

@observer
class Field<T: string | number> extends React.Component<Props<T>> {
    static defaultProps = {
        colSpan: 12,
        required: false,
        spaceAfter: 0,
    };

    @observable open = false;

    @computed get selectedType() {
        const {type, types} = this.props;

        if (!types) {
            return undefined;
        }

        return types.find((currentType) => currentType.value === type);
    }

    @action handleArrowMenuOpen = () => {
        this.open = true;
    };

    @action handleArrowMenuClose = () => {
        this.open = false;
    };

    @action handleTypeChange: (type: T) => void = (type) => {
        const {onTypeChange} = this.props;

        if (!onTypeChange) {
            return;
        }

        this.open = false;
        onTypeChange(type);
    };

    renderType() {
        const {selectedType} = this;

        if (!selectedType) {
            return <span />;
        }

        return (
            <button className={fieldStyles.type} onClick={this.handleArrowMenuOpen} type="button">
                <span className={fieldStyles.typeDelimiter}>•</span>
                {selectedType.label}<Icon className={fieldStyles.typeIcon} name="su-angle-down" />
            </button>
        );
    }

    render() {
        const {
            children,
            colSpan,
            description,
            error,
            id,
            label,
            required,
            skin,
            spaceAfter,
            types,
        } = this.props;

        const {selectedType} = this;

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles[skin]]: !!skin,
                [fieldStyles.error]: !!error,
            }
        );

        return (
            <Grid.Item
                className={gridStyles.gridItem}
                colSpan={colSpan}
                spaceAfter={spaceAfter}
            >
                <div className={fieldClass}>
                    {label &&
                        <label
                            className={fieldStyles.label}
                            htmlFor={id}
                        >
                            {label}
                            {selectedType && types &&
                                <ArrowMenu
                                    anchorElement={this.renderType()}
                                    onClose={this.handleArrowMenuClose}
                                    open={this.open}
                                >
                                    <ArrowMenu.SingleItemSection
                                        onChange={this.handleTypeChange}
                                        value={selectedType.value}
                                    >
                                        {types.map((type) => (
                                            <ArrowMenu.Item key={type.value} value={type.value}>
                                                {type.label}
                                            </ArrowMenu.Item>
                                        ))}
                                    </ArrowMenu.SingleItemSection>
                                </ArrowMenu>
                            }
                            {required && ' *'}
                        </label>
                    }
                    {children}
                    {description &&
                        <div className={fieldStyles.descriptionLabel}>
                            {description}
                        </div>
                    }
                    <div className={fieldStyles.errorLabel}>
                        {error}
                    </div>
                </div>
            </Grid.Item>
        );
    }
}

export default Field;
