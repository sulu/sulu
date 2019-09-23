// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed} from 'mobx';
import {CroppedText} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {Map, Marker, TileLayer, Tooltip} from 'react-leaflet';
import Icon from 'sulu-admin-bundle/components/Icon/Icon';
import type {Location as LocationValue} from '../../types';
import locationStyles from './location.scss';
import LocationOverlay from './LocationOverlay';

type Props = {|
    disabled: boolean,
    onChange: (value: ?LocationValue) => void,
    value: ?LocationValue,
|};

@observer
class Location extends React.Component<Props> {
    @observable overlayOpen: boolean = false;

    @action handleEditButtonClick = () => {
        this.overlayOpen = true;
    };

    @action handleOverlayConfirm = (newValue: ?LocationValue) => {
        this.overlayOpen = false;
        this.props.onChange(newValue);
    };

    @action handleOverlayClose = () => {
        this.overlayOpen = false;
    };

    @computed get label() {
        const {value} = this.props;

        if (value) {
            return translate('sulu_location.latitude') + ': ' + value.lat + ', '
                + translate('sulu_location.longitude') + ': ' + value.long + ', '
                + translate('sulu_location.zoom') + ': ' + value.zoom;
        }

        return translate('sulu_location.select_location');
    }

    @computed get hasAdditionalInformation() {
        const {code, country, number, street, title, town} = this.props.value;

        return code || country || number || street || title || town;
    }

    render() {
        const {
            disabled,
            value,
        } = this.props;

        return (
            <div className={locationStyles.locationContainer}>
                <div className={locationStyles.locationHeader}>
                    <button
                        className={locationStyles.locationHeaderButton}
                        disabled={disabled}
                        onClick={this.handleEditButtonClick}
                        type="button"
                    >
                        <Icon name="su-map-pin" />
                    </button>
                    <div className={locationStyles.locationHeaderLabel}>
                        <CroppedText>{this.label}</CroppedText>
                    </div>
                </div>
                {value &&
                    <Map
                        attributionControl={false}
                        center={[value.lat, value.long]}
                        className={locationStyles.locationMap}
                        doubleClickZoom={false}
                        dragging={false}
                        keyboard={false}
                        scrollWheelZoom={false}
                        tap={false}
                        zoom={value.zoom}
                        zoomControl={false}
                    >
                        <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                        <Marker interactive={false} position={[value.lat, value.long]}>
                            {this.hasAdditionalInformation &&
                                <Tooltip permanent={true}>
                                    <div>{value.title}</div>
                                    <div>{value.street} {value.number}</div>
                                    <div>{value.code} {value.town}</div>
                                    <div>{value.country}</div>
                                </Tooltip>
                            }
                        </Marker>
                    </Map>
                }
                <LocationOverlay
                    initialValue={value}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </div>
        );
    }
}

export default Location;
