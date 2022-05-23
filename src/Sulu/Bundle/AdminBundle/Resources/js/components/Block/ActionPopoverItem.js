// @flow
import React from 'react';
import Icon from '../Icon';
import actionPopoverItemStyles from './actionPopoverItem.scss';

type Props = {
    icon: string,
    index: number,
    label: string,
    onClick: (index: number) => void,
};

export default class ActionPopoverItem extends React.PureComponent<Props> {
    handleClick = () => {
        const {index, onClick} = this.props;

        onClick(index);
    };

    render() {
        const {
            icon,
            index,
            label,
        } = this.props;

        return (
            <li key={index}>
                <button
                    className={actionPopoverItemStyles.action}
                    onClick={this.handleClick}
                    type="button"
                >
                    <Icon
                        className={actionPopoverItemStyles.icon}
                        name={icon}
                    />
                    {label}
                </button>
            </li>

        );
    }
}
