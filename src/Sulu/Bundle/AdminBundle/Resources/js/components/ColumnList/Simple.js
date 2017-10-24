// @flow
import React from 'react';
import Icon from '../Icon';
import type {SimpleProps} from './types';
import toolbarStyles from './toolbar.scss';

export default class Simple extends React.Component<SimpleProps> {
    handleClick = () => {
        const handleClick = this.props.onClick;

        if (!handleClick) {
            return;
        }

        this.props.onClick(this.props.index);
    };

    render = () => {
        const {index, icon} = this.props;

        return (
            <div key={index} onClick={this.handleClick} className={toolbarStyles.item}>
                <Icon name={icon} />
            </div>
        );
    };
}

