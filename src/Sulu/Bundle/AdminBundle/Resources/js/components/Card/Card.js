// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import cardStyles from './card.scss';

type Props = {|
    children: Node,
    onEdit?: () => void,
    onRemove?: () => void,
|};

export default class Card extends React.Component<Props> {
    render() {
        const {children, onEdit, onRemove} = this.props;

        return (
            <section className={cardStyles.card}>
                <div className={cardStyles.icon}>
                    {onEdit && <Icon name="su-pen" onClick={onEdit} />}
                    {onRemove && <Icon name="su-trash-alt" onClick={onRemove} />}
                </div>
                {children}
            </section>
        );
    }
}
