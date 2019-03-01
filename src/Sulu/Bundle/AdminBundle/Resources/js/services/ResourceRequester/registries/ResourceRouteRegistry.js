// @flow
import {isObservableArray, toJS} from 'mobx';
import symfonyRouting from 'fos-jsrouting/router';
import type {EndpointConfiguration} from '../types';

function transformParameters(parameters) {
    return Object.keys(parameters)
        .filter((parameterKey) => parameters[parameterKey] !== undefined)
        .reduce((transformedParameters, parameterKey) => {
            const parameterValue = parameters[parameterKey];

            transformedParameters[parameterKey] = Array.isArray(parameterValue) || isObservableArray(parameterValue)
                ? parameterValue.join(',')
                : toJS(parameterValue);

            return transformedParameters;
        }, {});
}

class ResourceRouteRegistry {
    endpoints: EndpointConfiguration = {};

    configurationPromises: {[string]: Promise<Object>} = {};

    clear() {
        this.endpoints = {};
        this.configurationPromises = {};
    }

    setRoutingData(data: Object) {
        symfonyRouting.setRoutingData(data);
    }

    setEndpoints(endpoints: EndpointConfiguration) {
        this.endpoints = endpoints;
    }

    getDetailUrl(resourceKey: string, parameters: Object = {}) {
        if (!this.endpoints[resourceKey]) {
            throw new Error('There are no routes for the resourceKey "' + resourceKey + '"!');
        }

        if (!this.endpoints[resourceKey].routes.detail) {
            throw new Error('There is no detail route for the resourceKey "' + resourceKey + '"');
        }

        return symfonyRouting.generate(
            this.endpoints[resourceKey].routes.detail,
            transformParameters(parameters)
        );
    }

    getListUrl(resourceKey: string, parameters: Object = {}) {
        if (!this.endpoints[resourceKey]) {
            throw new Error('There are no routes for the resourceKey "' + resourceKey + '"!');
        }

        if (!this.endpoints[resourceKey].routes.list) {
            throw new Error('There is no list route for the resourceKey "' + resourceKey + '"');
        }

        return symfonyRouting.generate(
            this.endpoints[resourceKey].routes.list,
            transformParameters(parameters)
        );
    }
}

export default new ResourceRouteRegistry();
