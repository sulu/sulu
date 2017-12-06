// @flow
import React from 'react';
import classNames from 'classnames';
import {Icon} from 'sulu-admin-bundle/components';
import type {Point} from './types';
import imageFocusPointCellStyles from './imageFocusPointCell.scss';

const ARROW_UP_ICON = 'arrow-up';

type Props = {
    size: number,
    value: Point,
    active: boolean,
    onClick?: (value: Point) => void,
    arrowDirection?: '-90deg' | '-45deg' | '0deg' | '45deg' | '90deg' | '125deg' | '180deg' | '225deg',
};

export default class ImageFocusPointCell extends React.PureComponent<Props> {
    static defaultProps = {
        active: false,
        showArrow: true,
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
            arrowDirection,
        } = this.props;
        const buttonStyle = {
            width: `${size}%`,
            height: `${size}%`,
        };
        const focusPointClass = classNames(
            imageFocusPointCellStyles.imageFocusPointCell,
            {
                [imageFocusPointCellStyles.active]: active,
            }
        );
        const iconStyle = arrowDirection ? {transform: `rotate(${arrowDirection})`} : {};

        return (
            <button
                style={buttonStyle}
                onClick={this.handleClick}
                disabled={active}
                className={focusPointClass}
            >
                {!!arrowDirection && !active &&
                    <div style={iconStyle}>
                        <Icon name={ARROW_UP_ICON} />
                    </div>
                }
            </button>
        );
    }
}
