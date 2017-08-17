// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import labelStyles from './label.scss';

type Props = {
    onClick: () => void,
    children: string,
    icon?: string,
};

const TOGGLE_ICON = 'chevron-down';

export default class Label extends React.PureComponent<Props> {
    button: ElementRef<'button'>;

    /** @public **/
    getDimensions(): ClientRect {
        return this.button.getBoundingClientRect();
    }

    setButton = (button: ElementRef<'button'>) => this.button = button;

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
