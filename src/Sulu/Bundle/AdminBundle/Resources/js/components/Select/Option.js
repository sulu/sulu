// @flow
import Icon from '../Icon';
import React from 'react';
import type {SelectData} from './types';
import classnames from 'classnames';
import itemStyles from './selectItem.scss';

export default class Option extends React.PureComponent {
    props: {
        selected: boolean,
        disabled: boolean,
        value?: string,
        children?: string,
        onClick?: (SelectData) => void,
    };

    static defaultProps = {
        disabled: false,
        selected: false,
    };

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick({
                value: this.props.value || '',
                label: this.props.children || '',
            });
        }
    };

    render() {
        const classNames = classnames({
            [itemStyles.selectItem]: true,
            [itemStyles.disabled]: this.props.disabled,
            [itemStyles.selected]: this.props.selected,
        });

        return (
            <li className={classNames}>
                <button onClick={this.handleButtonClick} disabled={this.props.disabled}>
                    {this.props.selected ? <Icon className={itemStyles.icon} name="check" /> : ''}
                    {this.props.children}
                </button>
            </li>
        );
    }
}
