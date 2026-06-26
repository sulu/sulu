// @flow

function flushPromises(): Promise<void> {
    return Promise.resolve().then(() => undefined);
}

function waitForReaction(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve));
}

export {
    flushPromises,
    waitForReaction,
};
