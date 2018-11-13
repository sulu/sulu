// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Icon from '../Icon';
import singleItemSelectionStyles from './singleItemSelection.scss';
import type {Button} from './types';

type Props = {|
    children?: Node,
    disabled: boolean,
    emptyText?: string,
    leftButton: Button,
    onRemove?: () => void,
|};

export default class SingleItemSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    render() {
        const {children, disabled, emptyText, leftButton, onRemove} = this.props;
        const {icon, onClick} = leftButton;

        const singleItemSelectionClass = classNames(
            singleItemSelectionStyles.singleItemSelection,
            {
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
                                {emptyText}
                            </div>
                        }
                    </div>
                    {onRemove &&
                        <button
                            className={singleItemSelectionStyles.removeButton}
                            disabled={disabled}
                            onClick={onRemove}
                            type="button"
                        >
                            <Icon name="su-trash-alt" />
                        </button>
                    }
                </div>
            </div>
        );
    }
}
