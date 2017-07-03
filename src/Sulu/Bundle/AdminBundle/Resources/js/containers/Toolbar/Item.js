// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';

export default class Item extends React.PureComponent {
    props: {
        title: string,
        icon: string,
        enabled: boolean,
        onClick: () => void,
    };

    static defaultProps = {
        enabled: true,
    };

    handleClick = () => {
        if (this.props.enabled) {
            this.props.onClick();
        }
    };

    render() {
        const liClassNames = {
            [`${itemStyles['item']}`]: true,
            [`${itemStyles['item-disabled']}`]: !this.props.enabled,
        };

        return (
            <li className={classNames(liClassNames)} onClick={this.handleClick}>
                <Icon className={classNames(itemStyles.icon)} name={this.props.icon} />
                <span className={classNames(itemStyles.title)}>{this.props.title}</span>
            </li>
        );
    }
}
