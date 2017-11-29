// @flow
import React from 'react';
import classNames from 'classnames';
import {Icon} from 'sulu-admin-bundle/components';
import type {FocusPoint} from './types';
import imageFocusPointStyles from './imageFocusPoint.scss';

const FOCUS_POINT_MATRIX_SIZE = 3;
const ARROW_UP_ICON = 'arrow-up';
const ARROW_DOWN_ICON = 'arrow-down';
const ARROW_LEFT_ICON = 'arrow-left';
const ARROW_RIGHT_ICON = 'arrow-right';

type Props = {
    image: string,
    value: FocusPoint,
    onChange: (value: FocusPoint) => void,
};

export default class ImageFocusPoint extends React.PureComponent<Props> {
    createFocusPoint(icon: ?string, active: boolean = false) {
        const focusPointClass = classNames(
            imageFocusPointStyles.focusPoint,
            {
                [imageFocusPointStyles.active]: active,
            }
        );

        return (
            <button className={focusPointClass}>
                {icon &&
                    <Icon name={icon} />
                }
            </button>
        );
    }

    createFocusPoints(selectedPoint: FocusPoint) {
        const points = [];

        for (let x = 0; x < FOCUS_POINT_MATRIX_SIZE; x++) {
            for (let y = 0; y < FOCUS_POINT_MATRIX_SIZE; y++) {
                if (selectedPoint.x === x && selectedPoint.y === y) {
                    points.push(this.createFocusPoint(null, true));
                } else if (this.isLeftOfSelectedPoint(selectedPoint, x, y)) {
                    points.push(this.createFocusPoint(ARROW_LEFT_ICON));
                } else if (this.isRightOfSelectedPoint(selectedPoint, x, y)) {
                    points.push(this.createFocusPoint(ARROW_RIGHT_ICON));
                } else {
                    points.push(this.createFocusPoint(null));
                }
            }
        }

        return points;
    }

    isLeftOfSelectedPoint(selectedPoint: FocusPoint, x: number, y: number) {
        return selectedPoint.x + 1 === x && selectedPoint.x + 1 <= FOCUS_POINT_MATRIX_SIZE && selectedPoint.y === y;
    }

    isRightOfSelectedPoint(selectedPoint: FocusPoint, x: number, y: number) {
        return selectedPoint.x + 1 === x && selectedPoint.x + 1 <= FOCUS_POINT_MATRIX_SIZE && selectedPoint.y === y;
    }

    render() {
        const {
            image,
            value,
        } = this.props;

        return (
            <div className={imageFocusPointStyles.imageFocusPoint}>
                <div className={imageFocusPointStyles.focusPoints}>
                    {this.createFocusPoints(value)}
                </div>
                <img className={imageFocusPointStyles.image} src={image} />
            </div>
        );
    }
}
