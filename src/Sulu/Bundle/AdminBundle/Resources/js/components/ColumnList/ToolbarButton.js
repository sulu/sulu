// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {ToolbarButton as ToolbarButtonProps} from './types';
import toolbarStyles from './toolbar.scss';

export default class ToolbarButton extends React.Component<ToolbarButtonProps> {
    static defaultProps = {
        skin: 'primary',
    };

    handleClick = () => {
        this.props.onClick(this.props.columnIndex);
    };

    render = () => {
        const {icon, skin} = this.props;

        const className = classNames(
            toolbarStyles.item,
            toolbarStyles[skin]
        );

        return (
            <div className={className} onClick={this.handleClick}>
                <Icon name={icon} />
            </div>
        );
    };
}

