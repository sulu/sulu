// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, computed, observable, toJS} from 'mobx';
import classNames from 'classnames';
import type {Value as ImageValue} from '../SingleMediaSelection/types';
import SingleMediaSelection from '../SingleMediaSelection';
import type {Hotspot, Value, RenderHotspotFormCallback} from './types';
import ImageRenderer from './ImageRenderer';
import FormRenderer from './FormRenderer';
import imageMapStyles from './imageMap.scss';

type Props = {
    defaultFormType: string,
    disabled: boolean,
    formTypes: {[string]: string},
    locale: IObservableValue<string>,
    onChange: (data: Value) => void,
    renderHotspotForm: RenderHotspotFormCallback,
    valid: boolean,
    value: Value,
};

const MEDIA_TYPES = ['image'];

@observer
class ImageMap extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
        value: {
            imageId: undefined,
            hotspots: [],
        },
    };

    @observable selectedIndex: number = 0;
    @observable imageValue: ImageValue = {
        displayOption: undefined,
        id: undefined,
    };

    @action componentDidMount() {
        const {value: {imageId}} = this.props;

        this.imageValue = {
            displayOption: undefined,
            id: imageId,
        };
    }

    @action componentDidUpdate() {
        const {value: {imageId}, formTypes} = this.props;

        if (this.imageValue.id !== imageId) {
            this.imageValue = {
                displayOption: undefined,
                id: imageId,
            };
        }

        if (Object.keys(formTypes).length === 0) {
            throw new Error('There needs to be at least one form type specified!');
        }
    }

    handleImageChange = ({id}: ImageValue) => {
        const {onChange} = this.props;

        onChange({
            imageId: id,
            hotspots: [],
        });
    };

    handleSelectionChange = (index: number, selection: Object) => {
        const {onChange, value} = this.props;

        const hotspots = toJS(value.hotspots);
        hotspots[index].hotspot = {
            ...hotspots[index].hotspot,
            ...selection,
        };

        onChange({
            ...value,
            hotspots,
        });
    };

    handleHotspotTypeChange = (index: number, type: string) => {
        const {onChange, value} = this.props;

        const hotspots = toJS(value.hotspots);
        hotspots[index].hotspot = {type};

        onChange({
            ...value,
            hotspots,
        });
    };

    handleTypeChange = (index: number, type: string) => {
        const {onChange, value} = this.props;

        const hotspots = toJS(value.hotspots);
        hotspots[index].type = type;

        onChange({
            ...value,
            hotspots,
        });
    };

    @action handleHotspotRemove = (index: number) => {
        const {onChange, value} = this.props;

        onChange({
            ...value,
            hotspots: toJS(value.hotspots).filter((hotspot, hotspotIndex) => hotspotIndex !== index),
        });

        this.selectedIndex = Math.max(0, this.selectedIndex - 1);
    };

    @action handleHotspotSelect = (index: number) => {
        this.selectedIndex = index;
    };

    getDefaultHotspotData = (): Hotspot => {
        const {defaultFormType} = this.props;

        return {
            hotspot: {
                type: 'point',
            },
            type: defaultFormType,
        };
    };

    @action handleHotspotAdd = () => {
        const {onChange, value} = this.props;

        onChange({
            ...value,
            hotspots: [
                ...value.hotspots,
                this.getDefaultHotspotData(),
            ],
        });

        this.selectedIndex = value.hotspots.length;
    };

    @computed get currentHotspot() {
        const {value} = this.props;

        return value.hotspots[this.selectedIndex];
    }

    render() {
        const {value, valid, locale, disabled, renderHotspotForm, formTypes} = this.props;

        const cardClass = classNames(
            imageMapStyles.card,
            {
                [imageMapStyles.error]: !valid,
            }
        );

        return (
            <div>
                <SingleMediaSelection
                    className={!!value.imageId && imageMapStyles.singleItemSelection || undefined}
                    disabled={disabled}
                    locale={locale}
                    onChange={this.handleImageChange}
                    types={MEDIA_TYPES}
                    valid={valid}
                    value={this.imageValue}
                />

                {!!value.imageId &&
                    <div className={cardClass}>
                        <ImageRenderer
                            disabled={disabled}
                            locale={locale}
                            onSelectionChange={this.handleSelectionChange}
                            selectedIndex={this.selectedIndex}
                            value={value}
                        />

                        <div className={imageMapStyles.form}>
                            <FormRenderer
                                disabled={disabled}
                                formTypes={formTypes}
                                onHotspotAdd={this.handleHotspotAdd}
                                onHotspotRemove={this.handleHotspotRemove}
                                onHotspotSelect={this.handleHotspotSelect}
                                onHotspotTypeChange={this.handleHotspotTypeChange}
                                onTypeChange={this.handleTypeChange}
                                selectedIndex={this.selectedIndex}
                                value={value.hotspots}
                            >
                                {this.currentHotspot
                                    ? renderHotspotForm(
                                        this.currentHotspot,
                                        this.currentHotspot.type,
                                        this.selectedIndex
                                    )
                                    : null
                                }
                            </FormRenderer>
                        </div>
                    </div>
                }
            </div>
        );
    }
}

export default ImageMap;
