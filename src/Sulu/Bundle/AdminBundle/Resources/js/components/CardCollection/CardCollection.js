// @flow
import React, {Fragment} from 'react';
import type {Node} from 'react';
import Button from '../Button';
import Card from '../Card';
import cardCollectionStyles from './cardCollection.scss';

type Props<T> = {|
    renderCardContent: (T) => Node,
    value: Array<T>,
|};

export default class CardCollection<T> extends React.Component<Props<T>> {
    render() {
        const {renderCardContent, value} = this.props;

        return (
            <Fragment>
                <Button
                    className={cardCollectionStyles.addButton}
                    icon="su-plus"
                    skin="icon"
                />
                <section>
                    {value.map((cardData, index) => (
                        <div className={cardCollectionStyles.card} key={index}>
                            <Card>
                                {renderCardContent(cardData)}
                            </Card>
                        </div>
                    ))}
                </section>
            </Fragment>
        );
    }
}
