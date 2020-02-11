// @flow
import {RequestPromise} from '../Requester';
import ResourceRequester from './ResourceRequester';
import resourceRouteRegistry from './registries/resourceRouteRegistry';

export default ResourceRequester;
export {resourceRouteRegistry, RequestPromise};
