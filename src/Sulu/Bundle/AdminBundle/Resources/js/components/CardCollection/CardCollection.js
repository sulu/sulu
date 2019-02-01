// @flow
import React, {Fragment} from 'react';
import type {ChildrenArray, Element} from 'react';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import Card from '../Card';
import cardCollectionStyles from './cardCollection.scss';

type Props = {|
    children?: ChildrenArray<Element<typeof Card>> | false,
    onAdd?: () => void,
    onEdit?: (index: number) => void,
    onRemove?: (index: number) => void,
|};

export default class CardCollection extends React.Component<Props> {
    static Card = Card;

    render() {
        const {children, onAdd, onEdit, onRemove} = this.props;

        return (
            <Fragment>
                <Button
                    className={cardCollectionStyles.addButton}
                    icon="su-plus"
                    onClick={onAdd}
                    skin="secondary"
                >
                    {translate('sulu_admin.add')}
                </Button>
                <section>
                    {children && React.Children.map(children, (child, index) => (
                        <div className={cardCollectionStyles.card} key={index}>
                            {React.cloneElement(child, {id: index, onEdit, onRemove})}
                        </div>
                    ))}
                </section>
            </Fragment>
        );
    }
}
