// @flow
export default class Route {
    name: string;
    view: string;
    pattern: string;
    parameters: Object;

    constructor(name: string, view: string, pattern: string, parameters: Object = {}) {
        this.name = name;
        this.view = view;
        this.pattern = pattern;
        this.parameters = parameters;
    }
}
