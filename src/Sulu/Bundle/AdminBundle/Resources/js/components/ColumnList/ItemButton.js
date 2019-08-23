// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

type Props = {|
    icon: string,
    id: string | number,
    onClick: (id: string | number) => void,
    visible: boolean,
|};

export default class ItemButton extends React.Component<Props> {
    static defaultProps = {
        visible: true,
    };

    handleClick = () => {
        const {id, onClick} = this.props;

        if (!onClick) {
            return;
        }

        onClick(id);
    };

    render() {
        const {
            icon,
            visible,
        } = this.props;

        const iconClass = classNames({
            [itemStyles.button]: true,
            [itemStyles.visible]: visible,
        });

        return (
            <Icon className={iconClass} name={icon} onClick={this.handleClick} />
        );
    }
}
