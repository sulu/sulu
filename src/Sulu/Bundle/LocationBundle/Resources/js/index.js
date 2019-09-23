// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import L from 'leaflet';
import leafletMarkerIcon from 'leaflet/dist/images/marker-icon.png';
import leafletMarkerShadow from 'leaflet/dist/images/marker-shadow.png';
import {Location} from './containers/Form';

// leaflet requires that its stylesheet is embedded somewhere into the build
// eslint-disable-next-line no-unused-vars, import/order
import leafletStyles from 'leaflet/dist/leaflet.css';

// fix marker image urls of leaflet to display markers on maps
// https://github.com/PaulLeCam/react-leaflet/issues/453
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: leafletMarkerIcon,
    shadowUrl: leafletMarkerShadow,
});

fieldRegistry.add('location', Location);
