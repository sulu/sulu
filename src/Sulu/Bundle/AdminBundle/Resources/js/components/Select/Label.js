// @flow
import React from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import labelStyles from './label.scss';

const TOGGLE_ICON = 'chevron-down';

export default class Label extends React.PureComponent {
    props: {
        onClick?: () => void,
        children: string,
        icon?: string,
    };

    button: HTMLElement;

    /** @public **/
    getDimensions(): ClientRect {
        return this.button.getBoundingClientRect();
    }

    setButton = (button: HTMLElement) => this.button = button;

    render() {
        const classNames = classnames({
            [labelStyles.label]: true,
            [labelStyles.hasIcon]: !!this.props.icon,
        });

        return (
            <button
                ref={this.setButton}
                onClick={this.props.onClick}
                className={classNames}>
                {this.props.icon ? <Icon className={labelStyles.frontIcon} name={this.props.icon} /> : null}
                {this.props.children}
                <Icon className={labelStyles.toggle} name={TOGGLE_ICON} />
            </button>
        );
    }
}
