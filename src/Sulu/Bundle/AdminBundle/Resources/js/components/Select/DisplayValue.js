// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import displayValueStyles from './displayValue.scss';

type Props = {
    children: string,
    displayValueRef?: (button: ElementRef<'button'>) => void,
    icon?: string,
    onClick: () => void,
};

const TOGGLE_ICON = 'su-angle-down';

export default class DisplayValue extends React.PureComponent<Props> {
    button: ElementRef<'button'>;

    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        const {onClick} = this.props;

        event.preventDefault();
        onClick();
    };

    setButtonRef = (button: ?ElementRef<'button'>) => {
        const {displayValueRef} = this.props;
        if (displayValueRef && button) {
            displayValueRef(button);
        }
    };

    render() {
        const {icon, children} = this.props;
        const displayValueClass = classNames(
            displayValueStyles.displayValue,
            {
                [displayValueStyles.hasIcon]: !!icon,
            }
        );

        return (
            <button
                className={displayValueClass}
                onClick={this.handleClick}
                ref={this.setButtonRef}
                type="button"
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
