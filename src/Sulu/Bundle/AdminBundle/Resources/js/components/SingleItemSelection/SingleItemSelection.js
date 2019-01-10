// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Icon from '../Icon';
import Loader from '../Loader/Loader';
import singleItemSelectionStyles from './singleItemSelection.scss';
import type {Button} from './types';

type Props = {|
    children?: Node,
    disabled: boolean,
    emptyText?: string,
    leftButton: Button,
    loading: boolean,
    onRemove?: () => void,
    valid: boolean,
|};

export default class SingleItemSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        loading: false,
        valid: true,
    };

    render() {
        const {children, disabled, emptyText, leftButton, loading, onRemove, valid} = this.props;
        const {icon, onClick} = leftButton;

        const singleItemSelectionClass = classNames(
            singleItemSelectionStyles.singleItemSelection,
            {
                [singleItemSelectionStyles.error]: !valid,
                [singleItemSelectionStyles.disabled]: disabled,
            }
        );

        return (
            <div className={singleItemSelectionClass}>
                <button
                    className={singleItemSelectionStyles.button}
                    disabled={disabled}
                    onClick={onClick}
                    type="button"
                >
                    <Icon name={icon} />
                </button>
                <div className={singleItemSelectionStyles.itemContainer}>
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
            </div>
        );
    }
}
