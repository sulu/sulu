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
    displayValueRef?: (button: ElementRef<'button'>) => void,
};

const TOGGLE_ICON = 'chevron-down';

export default class DisplayValue extends React.PureComponent<Props> {
    button: ElementRef<'button'>;

    setButtonRef = (button: ?ElementRef<'button'>) => {
        const {displayValueRef} = this.props;
        if (displayValueRef && button) {
            displayValueRef(button);
        }
    };

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
                ref={this.setButtonRef}
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
