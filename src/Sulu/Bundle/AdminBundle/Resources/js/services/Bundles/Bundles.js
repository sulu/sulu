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

    if (typeof BUNDLE_ENTRIES_COUNT !== 'undefined') {
        setTimeout(() => {
            reject(
                'Timeout exceeded: Check if you correctly call the "bundleReady" function in the ' +
                '"./Resources/js/index.js" file of all your bundles.'
            );
        }, TIMEOUT);
    }
});

export {
    bundleReady,
    bundlesReadyPromise,
};
