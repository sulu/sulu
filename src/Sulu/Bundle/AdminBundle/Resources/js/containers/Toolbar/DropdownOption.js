// @flow
import Icon from '../../components/Icon';
import React from 'react';
import classNames from 'classnames';
import dropdownStyles from './dropdown.scss';

const ICON_CHECKMARK = 'check';

export default class DropdownOption extends React.PureComponent {
    props: {
        value: string,
        label: string,
        onClick: (optionValue: string) => void,
        selected?: boolean,
        disabled?: boolean,
    };

    handleOnClick = () => {
        const {onClick} = this.props;

        onClick(this.props.value);
    };

    render() {
        const {
            label,
            selected,
            disabled,
        } = this.props;

        return (
            <li className={classNames({
                [dropdownStyles.option]: true,
                [dropdownStyles.isSelected]: selected,
            })}>
                <button
                    disabled={disabled}
                    onClick={this.handleOnClick}>
                    {selected &&
                        <Icon name={ICON_CHECKMARK} className={dropdownStyles.optionSelectedIcon} />
                    }
                    {label}
                </button>
            </li>
        );
    }
}
