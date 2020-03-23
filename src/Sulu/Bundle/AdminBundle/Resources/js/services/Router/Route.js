// @flow
import pathToRegexp from 'path-to-regexp';
import {computed} from 'mobx';
import type {AttributeMap, Route as RouteType, RouteConfig} from './types';

export default class Route implements RouteType {
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

        if (config.children){
            this.children = config.children;
        }

        if (config.options) {
            this.options = config.options;
        }

        if (config.parent){
            this.parent = config.parent;
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
