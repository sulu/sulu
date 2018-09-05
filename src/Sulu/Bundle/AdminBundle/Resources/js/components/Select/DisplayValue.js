// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import displayValueStyles from './displayValue.scss';
import type {Skin} from './types';

type Props = {
    children: string,
    displayValueRef?: (button: ElementRef<'button'>) => void,
    icon?: string,
    onClick: () => void,
    skin: Skin,
};

export default class DisplayValue extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'default',
    };

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
        const {children, icon, skin} = this.props;

        const displayValueClass = classNames(
            displayValueStyles.displayValue,
            {
                [displayValueStyles.hasIcon]: !!icon,
                [displayValueStyles[skin]]: !!skin,
            }
        );

        return (
            <button
                ref={this.setButtonRef}
                onClick={this.handleClick}
                className={displayValueClass}
                type="button"
            >
                {!!icon &&
                    <Icon className={displayValueStyles.frontIcon} name={icon} />
                }
                <CroppedText>{children}</CroppedText>
                <Icon className={displayValueStyles.toggle} name="su-angle-down" />
            </button>
        );
    }
}
