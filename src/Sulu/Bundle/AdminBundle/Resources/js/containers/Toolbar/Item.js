// @flow
import React from 'react';
import Icon from '../../components/Icon';
import type {Item as ItemType} from './types';
import itemStyles from './item.scss';

export default class Item extends React.PureComponent<ItemType> {
    static defaultProps = {
        enabled: true,
    };

    handleClick = () => {
        if (this.props.enabled && this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        return (
            <button className={itemStyles.item} disabled={!this.props.enabled} onClick={this.handleClick}>
                <Icon className={itemStyles.icon} name={this.props.icon}/>
                <span className={itemStyles.title}>{this.props.title}</span>
            </button>
        );
    }
}
