// @flow
import React from 'react';
import type {Element} from 'react';
import classNames from 'classnames';
import ToolbarDropdownListOption from './ToolbarDropdownListOption';
import type {ToolbarDropdownOptionConfig} from './types';
import toolbarDropdownStyles from './toolbarDropdown.scss';

type Props = {|
    options: Array<ToolbarDropdownOptionConfig>,
    onClick: () => void,
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
            <div className={toolbarDropdownStyles.listContainer} onClick={onClick}>
                <ul className={className}>
                    {this.renderOptions()}
                </ul>
            </div>
        );
    }
}
