// @flow
import {isObservableArray, toJS} from 'mobx';
import symfonyRouting from 'fos-jsrouting/router';
import type {EndpointConfiguration} from '../types';
import {transformDateForUrl} from '../../../utils/Date';

function transformParameter(parameter) {
    return Array.isArray(parameter) || isObservableArray(parameter)
        ? parameter.map(transformParameter).join(',')
        : parameter instanceof Date
            ? transformDateForUrl(parameter)
            : parameter instanceof Object ? transformParameters(parameter) : toJS(parameter);
}

function transformParameters(parameters) {
    return Object.keys(parameters)
        .filter((parameterKey) => parameters[parameterKey] !== undefined)
        .reduce((transformedParameters, parameterKey) => {
            const value = toJS(parameters[parameterKey]);

            transformedParameters[parameterKey] = transformParameter(value);
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
            throw new Error(
                'There are no routes for the resourceKey "' + resourceKey + '"!' +
                '\n\nRegistered keys: ' + Object.keys(this.endpoints).sort().join(', ')
            );
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
            throw new Error(
                'There are no routes for the resourceKey "' + resourceKey + '"!' +
                '\n\nRegistered keys: ' + Object.keys(this.endpoints).sort().join(', ')
            );
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
