// @flow
import React from 'react';
import type {Node} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import classNames from 'classnames';
import ArrowMenu from '../ArrowMenu';
import Grid from '../Grid';
import type {ColSpan} from '../Grid';
import Icon from '../Icon';
import fieldStyles from './field.scss';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    colSpan: ColSpan,
    description?: string,
    error?: string,
    id?: string,
    label?: string,
    onTypeChange?: (type: string) => void,
    required: boolean,
    spaceAfter: ColSpan,
    type?: string,
    types?: Array<{label: string, value: string}>,
|};

@observer
class Field extends React.Component<Props> {
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

    handleTypeChange = (type: string) => {
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
            <button className={fieldStyles.type} onClick={this.handleArrowMenuOpen}>
                ({selectedType.label}<Icon className={fieldStyles.typeIcon} name="su-angle-down" />)
            </button>
        );
    }

    render() {
        const {
            children,
            id,
            description,
            error,
            label,
            required,
            colSpan,
            spaceAfter,
            types,
        } = this.props;

        const {selectedType} = this;

        const fieldClass = classNames(
            fieldStyles.field,
            {
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
                        <label className={fieldStyles.descriptionLabel}>
                            {description}
                        </label>
                    }
                    <label className={fieldStyles.errorLabel}>
                        {error}
                    </label>
                </div>
            </Grid.Item>
        );
    }
}

export default Field;
