// @flow

type Deferred<T> = {|
    promise: Promise<T>,
    resolve: (value: T) => void,
|};

function createDeferred<T>(): Deferred<T> {
    let resolve: (value: T) => void = () => {};
    const promise = new Promise((promiseResolve) => {
        resolve = promiseResolve;
    });

    return {promise, resolve};
}

export {
    createDeferred,
};
