// @flow
import type {Component} from 'react';

function findWithToolbarFunction(withToolbar: any, Component: Class<Component<*, *>>): Function {
    if (!withToolbar || !withToolbar.hasOwnProperty('mock') || withToolbar.mock.calls.length < 1) {
        throw new Error('withToolbar needs to be an mock');
    }

    for (const call of withToolbar.mock.calls) {
        if (call[0] === Component) {
            return call[1];
        }
    }

    throw new Error('function not found');
}

export {
    findWithToolbarFunction,
};
