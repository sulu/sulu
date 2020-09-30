// @flow
import React from 'react';
import type {ComponentType} from 'react';
import {buildHocDisplayName} from '../../utils/react';
import type {SelectionData} from './types';

type Props = {
    containerHeight: number,
    containerWidth: number,
    minHeight: number | typeof undefined,
    minWidth: number | typeof undefined,
    onChange: (value: ?SelectionData) => void,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

export default function withPercentageValues(Component: ComponentType<*>) {
    class WithPercentageValuesComponent extends React.Component<Props> {
        static defaultProps = {
            minHeight: undefined,
            minWidth: undefined,
            usePercentageValues: false,
        };

        handleChange = (value: ?SelectionData) => {
            const {onChange, containerHeight, containerWidth} = this.props;

            if (!value) {
                onChange(value);

                return;
            }

            onChange({
                ...value,
                left: value.left / containerWidth,
                top: value.top / containerHeight,
                width: value.width / containerWidth,
                height: value.height / containerHeight,
            });
        };

        getTransformedMinHeight = () => {
            const {containerHeight, minHeight} = this.props;

            if (!minHeight) {
                return minHeight;
            }

            return minHeight * containerHeight;
        };

        getTransformedMinWidth = () => {
            const {containerWidth, minWidth} = this.props;

            if (!minWidth) {
                return minWidth;
            }

            return minWidth * containerWidth;
        };

        getTransformedValue = () => {
            const {containerHeight, containerWidth, value} = this.props;

            if (!value) {
                return value;
            }

            return {
                ...value,
                left: value.left * containerWidth,
                top: value.top * containerHeight,
                width: value.width * containerWidth,
                height: value.height * containerHeight,
            };
        };

        render() {
            const {usePercentageValues} = this.props;

            if (!usePercentageValues) {
                return (
                    <Component {...this.props} />
                );
            }

            const props = {
                ...this.props,
                minHeight: this.getTransformedMinHeight(),
                minWidth: this.getTransformedMinWidth(),
                onChange: this.handleChange,
                value: this.getTransformedValue(),
            };

            return (
                <Component {...props} />
            );
        }
    }

    WithPercentageValuesComponent.displayName = buildHocDisplayName('withPercentageValues', Component);

    return WithPercentageValuesComponent;
}
