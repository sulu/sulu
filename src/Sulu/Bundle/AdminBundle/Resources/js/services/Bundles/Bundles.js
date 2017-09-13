// @flow
const TIMEOUT = 2000;
let resolveBundlesPromise;
let readyBundlesCount = 0;

declare var BUNDLE_ENTRIES_COUNT: number;

function bundleReady() {
    readyBundlesCount++;

    if (readyBundlesCount === BUNDLE_ENTRIES_COUNT) {
        resolveBundlesPromise();
    }
}

const bundlesReadyPromise: Promise<*> = new Promise((resolve, reject) => {
    resolveBundlesPromise = resolve;

    setTimeout(() => {
        reject(
            'Timeout exceeded: Check if you correctly call the "bundleReady" function in your Bundles ' +
            'JavaScript file.'
        );
    }, TIMEOUT);
});

export {
    bundleReady,
    bundlesReadyPromise,
};
