// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import singleItemSelectionStyles from './singleItemSelection.scss';
import type {Button} from './types';

type Props = {|
    children: Node,
    emptyText?: string,
    leftButton: Button,
    onRemove: () => void,
|};

export default class SingleItemSelection extends React.Component<Props> {
    render() {
        const {children, emptyText, leftButton, onRemove} = this.props;
        const {icon, onClick} = leftButton;

        return (
            <div className={singleItemSelectionStyles.singleItemSelection}>
                <button className={singleItemSelectionStyles.button} onClick={onClick} type="button">
                    <Icon name={icon} />
                </button>
                <div className={singleItemSelectionStyles.item}>
                    {children
                        ? children
                        : <div className={singleItemSelectionStyles.empty}>
                            {emptyText}
                        </div>
                    }
                </div>
                {onRemove &&
                    <button className={singleItemSelectionStyles.removeButton} onClick={onRemove} type="button">
                        <Icon name="su-trash-alt" />
                    </button>
                }
            </div>
        );
    }
}
