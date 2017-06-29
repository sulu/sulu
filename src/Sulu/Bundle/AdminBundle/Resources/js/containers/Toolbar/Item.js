// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';

export default class Item extends React.Component {
    props: {
        title: string,
        icon: string,
    };

    render() {
        return (
            <li className={classNames(itemStyles.item)}>
                <Icon className={classNames(itemStyles.icon)} name={this.props.icon} />
                <span className={classNames(itemStyles.title)}>{this.props.title}</span>
            </li>
        );
    }
}
