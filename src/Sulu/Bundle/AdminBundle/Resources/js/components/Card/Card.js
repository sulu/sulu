// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import cardStyles from './card.scss';

type Props<T> = {|
    children: Node,
    id?: T,
    onEdit?: (?T) => void,
    onRemove?: (?T) => void,
|};

export default class Card<T: string | number> extends React.Component<Props<T>> {
    handleEditClick = () => {
        const {id, onEdit} = this.props;

        if (onEdit) {
            onEdit(id);
        }
    };

    handleRemoveClick = () => {
        const {id, onRemove} = this.props;

        if (onRemove) {
            onRemove(id);
        }
    };

    render() {
        const {children, onEdit, onRemove} = this.props;

        return (
            <section className={cardStyles.card}>
                <div className={cardStyles.icons}>
                    {onEdit && <Icon name="su-pen" onClick={this.handleEditClick} />}
                    {onRemove && <Icon name="su-trash-alt" onClick={this.handleRemoveClick} />}
                </div>
                {children}
            </section>
        );
    }
}
