// @flow

const TIMEOUT = 2000;

class BundleRegistry {
    registeredBundlesCount: number = 0;

    resolve: () => void;

    add() {
        this.registeredBundlesCount++;

        if (this.registeredBundlesCount === BUNDLE_ENTRIES_COUNT) {
            this.resolve();
        }
    }

    wait(): Promise<*> {
        return new Promise((resolve, reject) => {
            this.resolve = resolve;

            setTimeout(() => {
                reject('Timeout exceeded: Check if you correctly registered your Bundles JavaScript file.');
            }, TIMEOUT);
        });
    }
}

export default new BundleRegistry;
