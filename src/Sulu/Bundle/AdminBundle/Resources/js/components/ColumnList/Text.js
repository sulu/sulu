// @flow
import React from 'react';
import Icon from '../Icon';
import type {SimpleProps} from './types';
import toolbarStyles from './toolbar.scss';

export default class Text extends React.Component<SimpleProps> {
    handleClick = () => {
        // open input
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

