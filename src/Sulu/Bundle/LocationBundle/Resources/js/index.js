// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import leaflet from 'leaflet';
import leafletMarkerIcon from 'leaflet/dist/images/marker-icon.png';
import leafletMarkerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import leafletMarkerShadow from 'leaflet/dist/images/marker-shadow.png';
import {Location} from './containers/Form';

// leaflet requires that its stylesheet is embedded somewhere into the build
// eslint-disable-next-line no-unused-vars, import/order
import leafletStyles from 'leaflet/dist/leaflet.css';

// fix marker image urls of leaflet to display markers on maps
// https://github.com/PaulLeCam/react-leaflet/issues/453
delete leaflet.Icon.Default.prototype._getIconUrl;
leaflet.Icon.Default.mergeOptions({
    iconUrl: leafletMarkerIcon,
    iconRetinaUrl: leafletMarkerIconRetina,
    shadowUrl: leafletMarkerShadow,
});

fieldRegistry.add('location', Location);
