// @flow

type Deferred<T> = {|
    promise: Promise<T>,
    resolve: (value: T) => void,
|};

function createDeferred<T>(): Deferred<T> {
    let resolve;
    const promise = new Promise((promiseResolve) => {
        resolve = promiseResolve;
    });

    if (!resolve) {
        throw new Error('Deferred promise resolver was not initialized');
    }

    return {promise, resolve};
}

export {
    createDeferred,
};
