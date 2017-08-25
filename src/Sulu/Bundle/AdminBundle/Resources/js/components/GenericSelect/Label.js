// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
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

    /** @public */
    getDimensions(): ClientRect {
        return this.button.getBoundingClientRect();
    }

    setButton = (button: ElementRef<'button'>) => this.button = button;

    render() {
        const {icon, onClick, children} = this.props;
        const labelClass = classNames({
            [labelStyles.label]: true,
            [labelStyles.hasIcon]: !!icon,
        });

        return (
            <button
                ref={this.setButton}
                onClick={onClick}
                className={labelClass}>
                {icon ? <Icon className={labelStyles.frontIcon} name={icon} /> : null}
                {children}
                <Icon className={labelStyles.toggle} name={TOGGLE_ICON} />
            </button>
        );
    }
}
