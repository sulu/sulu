// @flow
import pathToRegexp from 'path-to-regexp';
import {computed} from 'mobx';
import type {AttributeMap, Route as RouteType, RouteConfig} from './types';

export default class Route implements RouteType {
    attributeDefaults: AttributeMap = {};
    children: Array<RouteType> = [];
    name: string;
    options: Object = {};
    parent: ?RouteType = undefined;
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

        if (config.type) {
            this.type = config.type;
        }
    }

    @computed get availableAttributes(): Regexp {
        const attributes = [];
        pathToRegexp(this.path, attributes);

        return attributes.map((attribute) => attribute.name);
    }

    @computed get regexp(): Regexp {
        return pathToRegexp(this.path);
    }
}
