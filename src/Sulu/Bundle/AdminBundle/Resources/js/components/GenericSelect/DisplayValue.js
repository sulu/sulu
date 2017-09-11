// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import displayValueStyles from './displayValue.scss';

type Props = {
    onClick: () => void,
    children: string,
    icon?: string,
};

const TOGGLE_ICON = 'chevron-down';

export default class DisplayValue extends React.PureComponent<Props> {
    button: ElementRef<'button'>;

    /** @public */
    getDimensions(): ClientRect {
        return this.button.getBoundingClientRect();
    }

    setButton = (button: ElementRef<'button'>) => this.button = button;

    render() {
        const {icon, onClick, children} = this.props;
        const displayValueClass = classNames(
            displayValueStyles.displayValue,
            {
                [displayValueStyles.hasIcon]: !!icon,
            }
        );

        return (
            <button
                ref={this.setButton}
                onClick={onClick}
                className={displayValueClass}
            >
                {!!icon &&
                    <Icon className={displayValueStyles.frontIcon} name={icon} />
                }
                <CroppedText>{children}</CroppedText>
                <Icon className={displayValueStyles.toggle} name={TOGGLE_ICON} />
            </button>
        );
    }
}
