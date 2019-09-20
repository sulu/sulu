// @flow
import React from 'react';
import {action, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {Form, Input, Number} from 'sulu-admin-bundle/components';
import Overlay from 'sulu-admin-bundle/components/Overlay';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {Map, Marker, Popup, TileLayer} from 'react-leaflet';
import type {Location as LocationValue} from '../../types';
import locationOverlayStyles from './locationOverlay.scss';

type Props = {
    initialValue: ?LocationValue,
    onClose: () => void,
    onConfirm: (?LocationValue) => void,
    open: boolean,
};

@observer
class LocationOverlay extends React.Component<Props> {
    @observable title: ?string;
    @observable street: ?string;
    @observable number: ?string;
    @observable code: ?string;
    @observable town: ?string;
    @observable country: ?string;

    @observable lat: ?number;
    @observable long: ?number;
    @observable zoom: ?number;

    updateInitialDataDisposer: () => *;

    constructor(props: Props) {
        super(props);

        this.updateInitialDataDisposer = reaction(() => this.props.open, (newOpenValue) => {
            if (newOpenValue === true) {
                this.title = this.props.initialValue ? this.props.initialValue.title : null;
                this.street = this.props.initialValue ? this.props.initialValue.street : null;
                this.number = this.props.initialValue ? this.props.initialValue.number : null;
                this.code = this.props.initialValue ? this.props.initialValue.code : null;
                this.town = this.props.initialValue ? this.props.initialValue.town : null;
                this.lat = this.props.initialValue ? this.props.initialValue.lat : null;
                this.long = this.props.initialValue ? this.props.initialValue.long : null;
                this.zoom = this.props.initialValue ? this.props.initialValue.zoom : null;
            }
        });
    }

    componentWillUnmount() {
        this.updateInitialDataDisposer();
    }

    handleConfirm = () => {
        const {title, street, number, code, town, country, lat, long, zoom} = this;

        if (!lat || !long || !zoom) {
            this.props.onConfirm(null);

            return;
        }

        this.props.onConfirm({title, street, number, code, town, country, lat, long, zoom});
    };

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleStreetChange = (street: ?string) => {
        this.street = street;
    };

    @action handleNumberChange = (number: ?string) => {
        this.number = number;
    };

    @action handleCodeChange = (code: ?string) => {
        this.code = code;
    };

    @action handleTownChange = (town: ?string) => {
        this.town = town;
    };

    @action handleCountryChange = (country: ?string) => {
        this.country = country;
    };

    @action handleLatChange = (lat: ?number) => {
        this.lat = lat;
    };

    @action handleLongChange = (long: ?number) => {
        this.long = long;
    };

    @action handleZoomChange = (zoom: ?number) => {
        this.zoom = zoom;
    };

    render() {
        const {
            onClose,
            open,
        } = this.props;

        return (
            <Overlay
                confirmDisabled={!this.lat || !this.long || !this.zoom}
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={translate('sulu_admin.select_location')}
            >
                <div className={locationOverlayStyles.container}>
                    <Map
                        attributionControl={false}
                        center={[this.lat || 0, this.long || 0]}
                        className={locationOverlayStyles.map}
                        onClick={(v) => console.log('click', v)}
                        onMove={(v) => console.log('move', v)}
                        onZoom={(v) => console.log('zoom', v)}
                        zoom={this.zoom || 1}
                    >
                        <TileLayer
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />
                        <Marker
                            position={[this.lat || 0, this.long || 0]}
                            draggable={true}
                            onDrag={(v) => console.log('drag', v)}
                        >
                            <Popup>
                                    A pretty CSS3 popup. <br /> Easily customizable.
                            </Popup>
                        </Marker>
                    </Map>
                    <Form>
                        <Form.Field colSpan={4} label={translate('sulu_location.lat')} required={true}>
                            <Number onChange={this.handleLatChange} value={this.lat} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.long')} required={true}>
                            <Number onChange={this.handleLongChange} value={this.long} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.zoom')} required={true}>
                            <Number max={19} min={0} onChange={this.handleZoomChange} value={this.zoom} />
                        </Form.Field>
                    </Form>
                    <Form>
                        <Form.Section label={translate('sulu_location.additional_information')}>
                            <Form.Field label={translate('sulu_location.title')}>
                                <Input onChange={this.handleTitleChange} value={this.title} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.street')}>
                                <Input onChange={this.handleStreetChange} value={this.street} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.number')}>
                                <Input onChange={this.handleNumberChange} value={this.number} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.code')}>
                                <Input onChange={this.handleCodeChange} value={this.code} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.town')}>
                                <Input onChange={this.handleTownChange} value={this.town} />
                            </Form.Field>
                            <Form.Field label={translate('sulu_location.country')}>
                                <Input onChange={this.handleCountryChange} value={this.country} />
                            </Form.Field>
                        </Form.Section>
                    </Form>
                </div>
            </Overlay>
        );
    }
}

export default LocationOverlay;
