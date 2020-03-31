// @flow
import {pathToRegexp} from 'path-to-regexp';
import {computed} from 'mobx';
import type {AttributeMap, RouteConfig} from './types';

export default class Route {
    attributeDefaults: AttributeMap = {};
    children: Array<Route> = [];
    name: string;
    options: Object = {};
    parent: ?Route = undefined;
    path: string;
    rerenderAttributes: Array<string> = [];
    type: string;

    constructor(config: RouteConfig) {
        this.path = config.path;
        this.name = config.name;
        this.type = config.type;

        if (config.attributeDefaults){
            this.attributeDefaults = config.attributeDefaults;
        }

        if (config.options) {
            this.options = config.options;
        }

        if (config.rerenderAttributes) {
            this.rerenderAttributes = config.rerenderAttributes;
        }
    }

    @computed get availableAttributes(): Array<string> {
        const attributes = [];
        pathToRegexp(this.path, attributes);

        return attributes.map((attribute) => attribute.name);
    }

    @computed get regexp(): RegExp {
        return pathToRegexp(this.path);
    }
}
