// @flow
import type {SelectionData} from '../RectangleSelection/types';

export default class DimensionsConverter {
    computedWidth: number;
    computedHeight: number;
    realWidth: number;
    realHeight: number;

    constructor(computedWidth: number, computedHeight: number, realWidth: number, realHeight: number) {
        this.computedWidth = computedWidth;
        this.computedHeight = computedHeight;
        this.realWidth = realWidth;
        this.realHeight = realHeight;
    }

    computedHorizontalToReal(horizontal: number): number {
        return horizontal * this.realWidth / this.computedWidth;
    }

    computedVerticalToReal(vertical: number): number {
        return vertical * this.realHeight / this.computedHeight;
    }

    computedDataToReal(data: SelectionData): SelectionData {
        return {
            width: this.computedHorizontalToReal(data.width),
            height: this.computedVerticalToReal(data.height),
            left: this.computedHorizontalToReal(data.left),
            top: this.computedVerticalToReal(data.top),
        };
    }

    realDataToComputed(data: SelectionData): SelectionData {
        return {
            width: data.width * this.computedWidth / this.realWidth,
            height: data.height * this.computedHeight / this.realHeight,
            left: data.left * this.computedWidth / this.realWidth,
            top: data.top * this.computedWidth / this.realWidth,
        };
    }
}
