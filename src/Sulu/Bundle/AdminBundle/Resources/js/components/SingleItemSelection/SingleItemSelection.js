// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader/Loader';
import singleItemSelectionStyles from './singleItemSelection.scss';
import Button from './Button';
import type {Node} from 'react';
import type {Button as ButtonConfig, ItemButton} from './types';

type Props<T, U> = {|
    allowRemoveWhileItemDisabled: boolean,
    buttons: Array<ItemButton>,
    children?: Node,
    className?: string,
    disabled: boolean,
    emptyText?: string,
    id?: T,
    itemDisabled: boolean,
    leftButton: ButtonConfig<*>,
    loading: boolean,
    onItemClick?: (itemId: T, value: ?U) => void,
    onRemove?: () => void,
    rightButton?: ButtonConfig<*>,
    valid: boolean,
    value?: U,
|};

export default class SingleItemSelection<T: ?string | number, U> extends React.Component<Props<T, U>> {
    static defaultProps = {
        allowRemoveWhileItemDisabled: false,
        buttons: [],
        disabled: false,
        itemDisabled: false,
        loading: false,
        valid: true,
    };

    handleItemClick = () => {
        const {id, onItemClick, value} = this.props;

        if (onItemClick && id) {
            onItemClick(id, value);
        }
    };

    render() {
        const {
            allowRemoveWhileItemDisabled,
            buttons,
            children,
            className,
            disabled,
            itemDisabled,
            emptyText,
            leftButton,
            loading,
            onItemClick,
            onRemove,
            rightButton,
            valid,
        } = this.props;

        const singleItemSelectionClass = classNames(
            singleItemSelectionStyles.singleItemSelection,
            className,
            {
                [singleItemSelectionStyles.error]: !valid,
                [singleItemSelectionStyles.disabled]: disabled || itemDisabled,
            }
        );

        const itemClass = classNames(
            singleItemSelectionStyles.item,
            {
                [singleItemSelectionStyles.clickable]: !!onItemClick,
            }
        );

        return (
            <div className={singleItemSelectionClass}>
                <Button
                    {...leftButton}
                    disabled={disabled || itemDisabled}
                    location="left"
                />
                <div className={singleItemSelectionStyles.itemContainer}>
                    <div className={itemClass} onClick={this.handleItemClick} role="button">
                        {children
                            ? children
                            : <div className={singleItemSelectionStyles.empty}>
                                {loading ? '…' : emptyText}
                            </div>
                        }
                    </div>
                    {!loading && !disabled && !itemDisabled && buttons.map(
                        ({disabled: buttonDisabled, icon, onClick}, index) => (
                            <button
                                className={singleItemSelectionStyles.itemButton}
                                disabled={buttonDisabled}
                                key={index}
                                onClick={onClick}
                                type="button"
                            >
                                <Icon name={icon} />
                            </button>
                        )
                    )}
                    {onRemove && !loading && !disabled && (!itemDisabled || allowRemoveWhileItemDisabled) &&
                        <button
                            className={singleItemSelectionStyles.removeButton}
                            onClick={onRemove}
                            type="button"
                        >
                            <Icon name="su-trash-alt" />
                        </button>
                    }
                    {loading &&
                        <Loader className={singleItemSelectionStyles.loader} size={14} />
                    }
                </div>
                {rightButton &&
                    <Button
                        {...rightButton}
                        disabled={disabled || itemDisabled}
                        location="right"
                    />
                }
            </div>
        );
    }
}
