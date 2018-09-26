// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import Button from '../Button';
import dropdownButtonStyles from './dropdownButton.scss';

type Props = {|
    children?: Node,
    icon?: string,
    onClick?: (value: *) => void,
    skin: 'primary' | 'secondary' | 'link' | 'icon',
    value?: *,
|};

export default class DropdownButton extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'secondary',
    };

    render() {
        const {
            children,
            icon,
            onClick,
            skin,
            value,
        } = this.props;

        return (
            <Button
                className={dropdownButtonStyles.dropdownButton}
                icon={icon}
                onClick={onClick}
                skin={skin}
                value={value}
            >
                {children}
                <Icon className={dropdownButtonStyles.dropdownIcon} name="su-angle-down" />
            </Button>
        );
    }
}