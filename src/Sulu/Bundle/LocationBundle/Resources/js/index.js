// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import L from 'leaflet';
import leafletMarkerIcon from 'leaflet/dist/images/marker-icon.png';
import leafletMarkerShadow from 'leaflet/dist/images/marker-shadow.png';
import {Location} from './containers/Form';

// leaflet requires that its stylesheet is embedded somewhere into the build
// eslint-disable-next-line
import leafletStyles from 'leaflet/dist/leaflet.css';

// fix marker image urls of leaflet to display markers on maps
// https://stackoverflow.com/a/51222271
// https://github.com/PaulLeCam/react-leaflet/issues/453
L.Marker.prototype.options.icon = L.icon({
    iconUrl: leafletMarkerIcon,
    shadowUrl: leafletMarkerShadow,
});

fieldRegistry.add('location', Location);
