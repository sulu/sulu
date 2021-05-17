// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import {buildHocDisplayName} from '../../utils/react';
import type {ComponentType} from 'react';
import type {SelectionData} from './types';

type Props = {
    containerHeight: number,
    containerWidth: number,
    maxRadius: number | typeof undefined,
    minRadius: number | typeof undefined,
    onChange: (value: ?SelectionData) => void,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

export default function withPercentageValues(Component: ComponentType<*>) {
    @observer
    class WithPercentageValuesComponent extends React.Component<Props> {
        wrappedComponent = Component;

        static defaultProps = {
            maxRadius: undefined,
            minRadius: undefined,
            usePercentageValues: false,
        };

        handleChange = (value: ?SelectionData) => {
            const {containerHeight, containerWidth, onChange} = this.props;

            if (!value) {
                onChange(value);

                return;
            }

            onChange({
                ...value,
                left: value.left / containerWidth,
                top: value.top / containerHeight,
                radius: value.radius / containerWidth,
            });
        };

        @computed get transformedMaxRadius() {
            const {containerWidth, maxRadius} = this.props;

            if (!maxRadius) {
                return maxRadius;
            }

            return maxRadius * containerWidth;
        }

        @computed get transformedMinRadius() {
            const {containerWidth, minRadius} = this.props;

            if (!minRadius) {
                return minRadius;
            }

            return minRadius * containerWidth;
        }

        @computed get transformedValue() {
            const {containerHeight, containerWidth, value} = this.props;

            if (!value) {
                return value;
            }

            return {
                ...value,
                left: value.left * containerWidth,
                top: value.top * containerHeight,
                radius: value.radius * containerWidth,
            };
        }

        render() {
            const {usePercentageValues} = this.props;

            if (!usePercentageValues) {
                return (
                    <Component {...this.props} />
                );
            }

            const props = {
                ...this.props,
                maxRadius: this.transformedMaxRadius,
                minRadius: this.transformedMinRadius,
                onChange: this.handleChange,
                value: this.transformedValue,
            };

            return (
                <Component {...props} />
            );
        }
    }

    WithPercentageValuesComponent.displayName = buildHocDisplayName('withPercentageValues', Component);

    return WithPercentageValuesComponent;
}
