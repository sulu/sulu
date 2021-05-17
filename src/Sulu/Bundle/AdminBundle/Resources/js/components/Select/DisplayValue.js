// @flow
import React from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import displayValueStyles from './displayValue.scss';
import type {ElementRef, Node} from 'react';
import type {Skin} from './types';

type Props = {|
    children: Node,
    disabled: boolean,
    displayValueRef?: (button: ElementRef<'button'>) => void,
    icon?: string,
    onClick: () => void,
    skin: Skin,
|};

export default class DisplayValue extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
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
        const {children, disabled, icon, skin} = this.props;

        const displayValueClass = classNames(
            displayValueStyles.displayValue,
            displayValueStyles[skin],
            {
                [displayValueStyles.hasIcon]: !!icon,
            }
        );

        return (
            <button
                className={displayValueClass}
                disabled={disabled}
                onClick={!disabled ? this.handleClick : undefined}
                ref={this.setButtonRef}
                type="button"
            >
                {!!icon &&
                    <Icon className={displayValueStyles.frontIcon} name={icon} />
                }
                {typeof children === 'string' || typeof children === 'number'
                    ? <CroppedText>{String(children)}</CroppedText>
                    : children
                }
                <Icon className={displayValueStyles.toggle} name="su-angle-down" />
            </button>
        );
    }
}
