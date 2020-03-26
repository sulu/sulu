// @flow
import type {Node} from 'react';
import {action, observable} from 'mobx';

export default class AbstractFieldFilterType<T> {
    onChange: (value: T) => void;
    parameters: ?{[string]: mixed};
    @observable value: T;

    constructor(
        onChange: (value: T) => void,
        parameters: ?{[string]: mixed},
        value: T
    ) {
        this.onChange = onChange;
        this.parameters = parameters;
        this.value = value;
    }

    destroy() {}

    @action setValue(value: T): void {
        this.value = value;
    }

    // eslint-disable-next-line no-unused-vars
    getFormNode(): Node {
        return null;
    }

    // eslint-disable-next-line no-unused-vars
    getValueNode(value: T): Promise<Node> {
        return Promise.resolve(null);
    }
}
