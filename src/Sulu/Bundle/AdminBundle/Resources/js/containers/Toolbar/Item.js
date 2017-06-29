// @flow
import React from 'react';
import itemStyles from './item.scss';

export default class Item extends React.Component {
    props: {
        title: string,
    };

    render() {
        return (
            <li className={itemStyles.item}>{this.props.title}</li>
        );
    }
}
