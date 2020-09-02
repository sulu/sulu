// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {CircleSelectionRenderer} from 'sulu-admin-bundle/components/CircleSelection';
import {RectangleSelectionRenderer} from 'sulu-admin-bundle/components/RectangleSelection';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import type {IObservableValue} from 'mobx';
import type {Hotspot, Value} from './types';
import imageRendererStyles from './imageRenderer.scss';

type Props = {
    disabled: boolean,
    locale: IObservableValue<string>,
    onSelectionChange: (index: number, selection: Object) => void,
    selectedIndex: number,
    value: Value,
};

const MEDIA_RESOURCE_KEY = 'media';
const DEBOUNCE_TIME = 200;

@observer
class ImageRenderer extends React.Component<Props> {
    @observable containerSize: {height: number, width: number} = {width: 0, height: 0};
    @observable imageUrl: ?string;

    imageWrapperRef: ?ElementRef<'div'>;

    componentDidMount() {
        const {value, locale} = this.props;

        this.setImageUrl(value.imageId, locale);
        this.setContainerSize();

        const resizeObserver = new ResizeObserver(
            debounce(() => {
                this.setContainerSize();
            }, DEBOUNCE_TIME)
        );

        if (!this.imageWrapperRef) {
            return;
        }

        resizeObserver.observe(this.imageWrapperRef);
    }

    componentDidUpdate(prevProps: Props) {
        const {value, locale} = this.props;

        if (prevProps.value.imageId !== value.imageId) {
            this.setImageUrl(value.imageId, locale);
        }
    }

    @action setImageUrl = (imageId: ?number, locale: IObservableValue<string>) => {
        if (!imageId) {
            this.imageUrl = undefined;

            return;
        }

        const resourceStore = new ResourceStore(MEDIA_RESOURCE_KEY, imageId, {locale});

        when(
            () => !resourceStore.loading,
            action((): void => {
                this.imageUrl = resourceStore.data.url;
                resourceStore.destroy();
            })
        );
    };

    @action setContainerSize = () => {
        if (!this.imageWrapperRef) {
            return;
        }

        const {width, height} = this.imageWrapperRef.getBoundingClientRect();

        this.containerSize = {width, height};
    };

    setImageWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.imageWrapperRef = ref;
    };

    handleSelectionChange = (data: Object) => {
        const {onSelectionChange, selectedIndex} = this.props;

        onSelectionChange(selectedIndex, data);
    };

    getCommonSelectionProps = (hotspot: Hotspot, index: number) => {
        const {disabled, selectedIndex} = this.props;

        const entries = Object.entries(hotspot.hotspot).filter(([key]) => key !== 'type');
        const value = entries.length !== 0 ? Object.fromEntries(entries) : undefined;

        return {
            containerHeight: this.containerSize.height,
            containerWidth: this.containerSize.width,
            disabled: disabled || index !== selectedIndex,
            key: index,
            label: (index + 1).toString(),
            onChange: this.handleSelectionChange,
            percentageValues: true,
            round: false,
            value,
        };
    };

    renderCircleSelection = (hotspot: Hotspot, index: number) => {
        return (
            <CircleSelectionRenderer
                {...this.getCommonSelectionProps(hotspot, index)}
                filled={false}
                resizable={true}
            />
        );
    };

    renderPointSelection = (hotspot: Hotspot, index: number) => {
        return (
            <CircleSelectionRenderer
                {...this.getCommonSelectionProps(hotspot, index)}
                filled={true}
                resizable={false}
            />
        );
    };

    renderRectangleSelection = (hotspot: Hotspot, index: number) => {
        return (
            <RectangleSelectionRenderer
                {...this.getCommonSelectionProps(hotspot, index)}
                backdrop={false}
                forceRatio={false}
                minSizeNotification={false}
            />
        );
    };

    get sortedHotspots() {
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
        return (
            <div className={imageRendererStyles.container}>
                <div className={imageRendererStyles.wrapper} ref={this.setImageWrapperRef}>
                    {this.imageUrl &&
                        <img
                            className={imageRendererStyles.image}
                            src={this.imageUrl}
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
