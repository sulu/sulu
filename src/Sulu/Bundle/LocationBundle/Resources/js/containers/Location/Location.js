// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {CroppedText} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {Map, Marker, Popup, TileLayer} from 'react-leaflet';
import Icon from 'sulu-admin-bundle/components/Icon/Icon';
import type {Location as LocationValue} from '../../types';
import locationStyles from './location.scss';
import LocationOverlay from './LocationOverlay';

type Props = {|
    disabled: boolean,
    onChange: (value: ?LocationValue) => void,
    provider: string,
    providerOptions: Object,
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

    render() {
        const {
            disabled,
            value,
        } = this.props;

        const label = !value
            ? translate('sulu_admin.select_location')
            : Object.values(value).filter((v) => v).join(' / ');

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
                        <CroppedText>{label}</CroppedText>
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
                        <TileLayer
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />
                        <Marker position={[value.lat, value.long]}>
                            <Popup minWidth={90}>
                                <span>hello</span>
                            </Popup>
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
