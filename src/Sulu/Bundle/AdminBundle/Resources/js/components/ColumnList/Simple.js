// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {SimpleProps} from './types';
import toolbarStyles from './toolbar.scss';

export default class Simple extends React.Component<SimpleProps> {
    static defaultProps = {
        skin: 'primary',
    };

    handleClick = () => {
        this.props.onClick(this.props.index);
    };

    render = () => {
        const {index, icon, skin} = this.props;

        const className = classNames(
            toolbarStyles.item,
            toolbarStyles[skin]
        );

        return (
            <div key={index} onClick={this.handleClick} className={className}>
                <Icon name={icon} />
            </div>
        );
    };
}

