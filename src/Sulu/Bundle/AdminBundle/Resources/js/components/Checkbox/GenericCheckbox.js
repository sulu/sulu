// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {CheckboxProps} from './types';
import genericCheckboxStyles from './genericCheckbox.scss';

type Props = CheckboxProps & {
    className: string,
    icon?: string,
};

export default class GenericCheckbox extends React.PureComponent<Props> {
    static defaultProps = {
        className: '',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked, this.props.value);
        }
    };

    render() {
        const checkboxClass = classNames({
            [genericCheckboxStyles.checkbox]: true,
            [this.props.className]: !!this.props.className,
        });

        return (
            <label className={genericCheckboxStyles.genericCheckbox}>
                <span className={checkboxClass}>
                    <input
                        type="checkbox"
                        name={this.props.name}
                        checked={this.props.checked}
                        onChange={this.handleChange} />
                    <span>
                        {this.props.icon && <Icon name={this.props.icon} />}
                    </span>
                </span>
                {this.props.children && <span>{this.props.children}</span>}
            </label>
        );
    }
}
