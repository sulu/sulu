// @flow
import React from 'react';
import classNames from 'classnames';
import {Icon} from 'sulu-admin-bundle/components';
import type {Point} from './types';
import focusPointStyles from './focusPoint.scss';

const ARROW_UP_ICON = 'arrow-up';

type Props = {
    size: number,
    value: Point,
    active: boolean,
    onClick?: (value: Point) => void,
    showArrow: boolean,
    arrowDirection: number,
};

export default class FocusPoint extends React.PureComponent<Props> {
    static defaultProps = {
        active: false,
        showArrow: true,
        arrowDirection: 0,
    };

    handleClick = () => {
        const {
            value,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(value);
        }
    };

    render() {
        const {
            size,
            active,
            showArrow,
            arrowDirection,
        } = this.props;
        const buttonStyle = {
            width: `${size}%`,
            height: `${size}%`,
        };
        const iconStyle = {
            transform: `rotate(${arrowDirection}deg)`,
        };
        const focusPointClass = classNames(
            focusPointStyles.focusPoint,
            {
                [focusPointStyles.active]: active,
            }
        );

        return (
            <button
                style={buttonStyle}
                onClick={this.handleClick}
                disabled={active}
                className={focusPointClass}
            >
                {showArrow && !active &&
                    <div style={iconStyle}>
                        <Icon name={ARROW_UP_ICON} />
                    </div>
                }
            </button>
        );
    }
}
