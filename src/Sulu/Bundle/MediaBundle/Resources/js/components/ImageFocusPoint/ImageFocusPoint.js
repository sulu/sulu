// @flow
import React from 'react';
import type {FocusPoint} from './types';
import imageFocusPointStyles from './imageFocusPoint.scss';

const FOCUS_POINT_MATRIX_SIZE = 3;

type Props = {
    image: string,
    value: FocusPoint,
    onChange: (value: FocusPoint) => void,
};

export default class ImageFocusPoint extends React.PureComponent<Props> {
    createMatrix(selectedPoint: FocusPoint) {
        const matrix = [];

        for (let rowIndex = 0; rowIndex < FOCUS_POINT_MATRIX_SIZE; rowIndex++) {
            const row = [];
            for (let columnIndex = 0; columnIndex < FOCUS_POINT_MATRIX_SIZE; columnIndex++) {
                if (selectedPoint.x === columnIndex && selectedPoint.y === rowIndex) {
                    row.push(1);
                } else {
                    row.push(0);
                }
            }

            matrix.push();
        }

        return matrix;
    }

    createFocusPoints() {
        const points = [];

        for (let x = 0; x < FOCUS_POINT_MATRIX_SIZE; x++) {
            for (let y = 0; y < FOCUS_POINT_MATRIX_SIZE; y++) {
                points.push(
                    <button className={imageFocusPointStyles.focusPoint}>
                        {`${x}, ${y}`}
                    </button>
                );
            }
        }

        return points;
    }

    render() {
        const {image} = this.props;

        return (
            <div className={imageFocusPointStyles.imageFocusPoint}>
                <div className={imageFocusPointStyles.focusPoints}>
                    {this.createFocusPoints()}
                </div>
                <img className={imageFocusPointStyles.image} src={image} />
            </div>
        );
    }
}
