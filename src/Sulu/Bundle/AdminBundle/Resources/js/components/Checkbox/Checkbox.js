// @flow
import React from 'react';
import classNames from 'classnames';
import type {CheckboxProps} from './types';
import checkboxStyles from './checkbox.scss';
import GenericCheckbox from './GenericCheckbox';

type Props = CheckboxProps & {
    skin: 'dark' | 'light',
};

const CHECKED_ICON = 'check';

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
    };

    render() {
        const checkboxClass = classNames({
            [checkboxStyles.checkbox]: true,
            [checkboxStyles[this.props.skin]]: true,
        });

        return (
            <GenericCheckbox
                className={checkboxClass}
                checked={this.props.checked}
                value={this.props.value}
                name={this.props.name}
                icon={this.props.checked ? CHECKED_ICON : undefined}
                onChange={this.props.onChange}>
                {this.props.children}
            </GenericCheckbox>
        );
    }
}
