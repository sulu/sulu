// @flow
import React from 'react';
import classNames from 'classnames';
import ToolbarDropdownListOption from './ToolbarDropdownListOption';
import type {ToolbarDropdownOptionConfig} from './types';
import toolbarDropdownStyles from './toolbarDropdown.scss';

type Props = {
    columnIndex?: number,
    options: Array<ToolbarDropdownOptionConfig>,
    skin?: 'primary' | 'secondary',
};

export default class ToolbarDropdownList extends React.Component<Props> {
    static defaultProps = {
        skin: 'primary',
    };

    renderOptions = () => {
        const {options} = this.props;

        return options.map((dropdownOptionConfig: ToolbarDropdownOptionConfig, columnIndex: number) => {
            const key = `option-${columnIndex}`;
            const {onClick, label} = dropdownOptionConfig;

            return (
                <ToolbarDropdownListOption columnIndex={this.props.columnIndex} key={key} onClick={onClick}>
                    {label}
                </ToolbarDropdownListOption>
            );
        });
    };

    render() {
        const {skin} = this.props;

        const className = classNames(
            toolbarDropdownStyles.list,
            toolbarDropdownStyles[skin]
        );

        return (
            <div className={toolbarDropdownStyles.listContainer}>
                <ul className={className}>
                    {this.renderOptions()}
                </ul>
            </div>
        );
    }
}
