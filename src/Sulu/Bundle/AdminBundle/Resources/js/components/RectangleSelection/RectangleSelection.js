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
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    forceRatio: boolean,
    label?: string,
    minHeight?: number,
    minSizeNotification: booelan,
    minWidth?: number,
    onChange: (s: ?SelectionData) => void,
    round: boolean,
    value: SelectionData | typeof undefined,
};

class RectangleSelection extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        forceRatio: true,
        minSizeNotification: true,
        round: true,
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

const RectangleSelectionComponent = withContainerSize(RectangleSelection, rectangleSelectionStyles.container);
RectangleSelectionComponent.Renderer = RectangleSelectionRenderer;

export default RectangleSelectionComponent;
