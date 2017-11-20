// @flow
import React from 'react';
import toolbarDropdownOptionStyles from './toolbarDropdownOption.scss';

type Props = {
    onClick: (columnIndex?: number) => void,
    columnIndex?: number,
    children: string,
};

export default class ToolbarDropdownListOption extends React.Component<Props> {
    handleClick = () => {
        const {columnIndex, onClick} = this.props;

        onClick(columnIndex);
    };

    render() {
        const {children} = this.props;
        return (
            <li>
                <button className={toolbarDropdownOptionStyles.option} onClick={this.handleClick}>
                    {children}
                </button>
            </li>
        );
    }
}
