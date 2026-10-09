// @flow

type ResizeObserverMock = {|
    getInstances: () => Array<any>,
    reset: () => void,
    trigger: (index?: number, entries?: Array<Object>) => void,
|};

function mockResizeObserver(): ResizeObserverMock {
    const instances = [];

    (window: any).ResizeObserver = jest.fn(function(callback) {
        this.callback = callback;
        this.disconnect = jest.fn();
        this.observe = jest.fn();
        this.unobserve = jest.fn();
        instances.push(this);
    });

    return {
        getInstances: () => instances,
        reset: () => {
            instances.splice(0, instances.length);
        },
        trigger: (index: number = 0, entries: Array<Object> = []) => {
            instances[index].callback(entries);
        },
    };
}

export default mockResizeObserver;
