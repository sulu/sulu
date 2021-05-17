// @flow
import React from 'react';
import {action, observable, toJS, computed} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {CircleSelection, RectangleSelection} from 'sulu-admin-bundle/components';
import imageRendererStyles from './imageRenderer.scss';
import type {Hotspot, Value} from './types';
import type {IObservableValue} from 'mobx';
import type {ElementRef} from 'react';

type Props = {
    disabled: boolean,
    locale: IObservableValue<string>,
    onFinish?: () => void,
    onSelectionChange: (index: number, selection: Object) => void,
    selectedIndex: number,
    value: Value,
};

const DEBOUNCE_TIME = 200;

@observer
class ImageRenderer extends React.Component<Props> {
    @observable imageWrapperSize: {height: number, width: number} = {width: 0, height: 0};

    imageWrapperRef: ?ElementRef<'div'>;

    componentDidMount() {
        this.setImageWrapperSize();

        const resizeObserver = new ResizeObserver(
            debounce(() => {
                this.setImageWrapperSize();
            }, DEBOUNCE_TIME)
        );

        if (!this.imageWrapperRef) {
            return;
        }

        resizeObserver.observe(this.imageWrapperRef);
    }

    @computed get imageUrl() {
        const {value: {imageId}, locale} = this.props;

        if (!imageId) {
            return undefined;
        }

        return '/admin/media/redirect/media/' + imageId + '?locale=' + locale;
    }

    @action setImageWrapperSize = () => {
        if (!this.imageWrapperRef) {
            return;
        }

        const {width, height} = this.imageWrapperRef.getBoundingClientRect();

        this.imageWrapperSize = {width, height};
    };

    setImageWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.imageWrapperRef = ref;
    };

    handleSelectionChange = (data: Object) => {
        const {onSelectionChange, selectedIndex} = this.props;

        onSelectionChange(selectedIndex, data);
    };

    getCommonSelectionProps = (hotspot: Hotspot, index: number) => {
        const {disabled, onFinish, selectedIndex} = this.props;

        const entries = Object.entries(hotspot.hotspot).filter(([key]) => key !== 'type');
        const value: Object | typeof undefined = entries.length !== 0 ? Object.fromEntries(entries) : undefined;

        return {
            containerHeight: this.imageWrapperSize.height,
            containerWidth: this.imageWrapperSize.width,
            disabled: disabled || index !== selectedIndex,
            key: index,
            label: (index + 1).toString(),
            onChange: this.handleSelectionChange,
            onFinish,
            usePercentageValues: true,
            round: false,
            value,
        };
    };

    renderCircleSelection = (hotspot: Hotspot, index: number) => {
        return (
            <CircleSelection
                {...this.getCommonSelectionProps(hotspot, index)}
                resizable={true}
                skin="outlined"
            />
        );
    };

    renderPointSelection = (hotspot: Hotspot, index: number) => {
        return (
            <CircleSelection
                {...this.getCommonSelectionProps(hotspot, index)}
                resizable={false}
                skin="filled"
            />
        );
    };

    renderRectangleSelection = (hotspot: Hotspot, index: number) => {
        return (
            <RectangleSelection
                {...this.getCommonSelectionProps(hotspot, index)}
                backdrop={false}
                minSizeNotification={false}
            />
        );
    };

    @computed get sortedHotspots() {
        const {value, selectedIndex} = this.props;

        const hotspots: Array<[number, Hotspot]> = Array.from(toJS(value.hotspots).entries());

        hotspots
            .sort(
                ([a], [b]) => {
                    if (a === selectedIndex) {
                        return 1;
                    }

                    if (b === selectedIndex) {
                        return -1;
                    }

                    return 0;
                });

        return hotspots;
    }

    render() {
        const {imageUrl} = this;

        return (
            <div className={imageRendererStyles.imageRenderer}>
                <div className={imageRendererStyles.imageRendererWrapper} ref={this.setImageWrapperRef}>
                    {imageUrl &&
                        <img
                            className={imageRendererStyles.image}
                            key={imageUrl}
                            src={imageUrl}
                        />
                    }

                    {this.sortedHotspots.map(([index, hotspotData]) => {
                        switch (hotspotData.hotspot.type) {
                            case 'circle':
                                return this.renderCircleSelection(hotspotData, index);
                            case 'point':
                                return this.renderPointSelection(hotspotData, index);
                            case 'rectangle':
                                return this.renderRectangleSelection(hotspotData, index);
                            default:
                                throw new Error(`Unexpected hotspot type "${hotspotData.hotspot.type}".`);
                        }
                    })}
                </div>
            </div>
        );
    }
}

export default ImageRenderer;
