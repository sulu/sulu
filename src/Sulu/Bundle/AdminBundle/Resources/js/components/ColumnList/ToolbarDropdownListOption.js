// @flow
import React from 'react';
import toolbarDropdownOptionStyles from './toolbarDropdownOption.scss';

type Props = {
    columnIndex?: number,
    children: string,
    disabled?: boolean,
    onClick: (columnIndex?: number) => void,
};

export default class ToolbarDropdownListOption extends React.Component<Props> {
    handleClick = () => {
        const {columnIndex, onClick} = this.props;

        onClick(columnIndex);
    };

    render() {
        const {children, disabled} = this.props;
        return (
            <li>
                <button className={toolbarDropdownOptionStyles.option} disabled={disabled} onClick={this.handleClick}>
                    {children}
                </button>
            </li>
        );
    }
}
