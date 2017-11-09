// @flow
import React from 'react';
import classNames from 'classnames';
import type {ToolbarDropdownOptionConfig} from './types';
import toolbarDropdownStyles from './toolbarDropdown.scss';

type Props = {
    index?: number,
    options: Array<ToolbarDropdownOptionConfig>,
    skin?: 'primary' | 'secondary',
};

export default class ToolbarDropdownList extends React.Component<Props> {
    static defaultProps = {
        skin: 'primary',
    };

    renderOptions = () => {
        const {options} = this.props;

        return options.map((dropdownOptionConfig: ToolbarDropdownOptionConfig, index: number) => {
            const key = `option-${index}`;
            const handleClick = () => {
                dropdownOptionConfig.onClick(this.props.index);
            };

            return (
                <li
                    className={toolbarDropdownStyles.option}
                    key={key}
                    value={this.props.index}
                >
                    <button className={toolbarDropdownStyles.button} onClick={handleClick}>
                        {dropdownOptionConfig.label}
                    </button>
                </li>
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
