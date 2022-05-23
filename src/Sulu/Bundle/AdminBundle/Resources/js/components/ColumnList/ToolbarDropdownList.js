// @flow
import React from 'react';
import classNames from 'classnames';
import ToolbarDropdownListOption from './ToolbarDropdownListOption';
import toolbarDropdownStyles from './toolbarDropdown.scss';
import type {ToolbarDropdownOptionConfig} from './types';
import type {Element} from 'react';

type Props = {|
    onClick: () => void,
    options: Array<ToolbarDropdownOptionConfig>,
    skin?: 'primary' | 'secondary',
|};

export default class ToolbarDropdownList extends React.Component<Props> {
    static defaultProps = {
        skin: 'primary',
    };

    renderOptions = (): Array<Element<typeof ToolbarDropdownListOption>> => {
        const {options} = this.props;

        return options.map((dropdownOptionConfig: ToolbarDropdownOptionConfig, columnIndex: number) => {
            const key = `option-${columnIndex}`;
            const {disabled, onClick, label} = dropdownOptionConfig;

            return (
                <ToolbarDropdownListOption
                    disabled={disabled}
                    key={key}
                    onClick={onClick}
                >
                    {label}
                </ToolbarDropdownListOption>
            );
        });
    };

    render() {
        const {onClick, skin} = this.props;

        const className = classNames(
            toolbarDropdownStyles.list,
            toolbarDropdownStyles[skin]
        );

        return (
            <button className={toolbarDropdownStyles.listContainer} onClick={onClick} type="button">
                <ul className={className}>
                    {this.renderOptions()}
                </ul>
            </button>
        );
    }
}
