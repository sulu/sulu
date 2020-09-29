// @flow
import React from 'react';
import type {Node} from 'react';
import withContainerSize from '../withContainerSize';
import type {SelectionData} from './types';
import RectangleSelectionRenderer from './RectangleSelectionRenderer';
import rectangleSelectionStyles from './rectangleSelection.scss';

type Props = {
    backdrop: boolean,
    children?: Node,
    disabled: boolean,
    forceRatio: boolean,
    label?: string,
    minHeight?: number,
    minSizeNotification: boolean,
    minWidth?: number,
    onChange: (s: ?SelectionData) => void,
    round: boolean,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

class RectangleSelection extends React.Component<Props & {
    containerHeight: number,
    containerWidth: number,
}> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        forceRatio: true,
        minSizeNotification: true,
        round: true,
        usePercentageValues: false,
    };

    render() {
        const {
            backdrop,
            children,
            containerHeight,
            containerWidth,
            disabled,
            forceRatio,
            label,
            minHeight,
            minSizeNotification,
            minWidth,
            onChange,
            usePercentageValues,
            round,
            value,
        } = this.props;

        return (
            <div className={rectangleSelectionStyles.selection}>
                {this.props.children}
                <RectangleSelectionRenderer
                    backdrop={backdrop}
                    containerHeight={containerHeight}
                    containerWidth={containerWidth}
                    disabled={disabled}
                    forceRatio={forceRatio}
                    label={label}
                    minHeight={minHeight}
                    minSizeNotification={minSizeNotification}
                    minWidth={minWidth}
                    onChange={onChange}
                    round={round}
                    usePercentageValues={usePercentageValues}
                    value={value}
                >
                    {children}
                </RectangleSelectionRenderer>
            </div>
        );
    }
}

export {
    RectangleSelection,
};

const Component = withContainerSize(RectangleSelection, rectangleSelectionStyles.container);

export default class RectangleSelectionComponent extends React.Component<Props> {
    static defaultProps = RectangleSelection.defaultProps;

    static Renderer = RectangleSelectionRenderer;

    render() {
        return <Component {...this.props} />;
    }
}
