// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Icon from '../Icon';
import Loader from '../Loader/Loader';
import singleItemSelectionStyles from './singleItemSelection.scss';
import Button from './Button';
import type {Button as ButtonConfig} from './types';

type Props = {|
    children?: Node,
    disabled: boolean,
    emptyText?: string,
    leftButton: ButtonConfig<*>,
    loading: boolean,
    onRemove?: () => void,
    rightButton?: ButtonConfig<*>,
    valid: boolean,
|};

export default class SingleItemSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        loading: false,
        valid: true,
    };

    render() {
        const {children, disabled, emptyText, leftButton, loading, onRemove, rightButton, valid} = this.props;

        const itemContainerClass = classNames(
            singleItemSelectionStyles.itemContainer,
            {
                [singleItemSelectionStyles.hasRightButton]: !!rightButton,
            }
        );

        const singleItemSelectionClass = classNames(
            singleItemSelectionStyles.singleItemSelection,
            {
                [singleItemSelectionStyles.error]: !valid,
                [singleItemSelectionStyles.disabled]: disabled,
            }
        );

        return (
            <div className={singleItemSelectionClass}>
                <Button
                    {...leftButton}
                    disabled={disabled}
                    location="left"
                />
                <div className={itemContainerClass}>
                    <div className={singleItemSelectionStyles.item}>
                        {children
                            ? children
                            : <div className={singleItemSelectionStyles.empty}>
                                {loading ? '…' : emptyText}
                            </div>
                        }
                    </div>
                    {onRemove && !loading &&
                        <button
                            className={singleItemSelectionStyles.removeButton}
                            disabled={disabled}
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
                        disabled={disabled}
                        location="right"
                    />
                }
            </div>
        );
    }
}
