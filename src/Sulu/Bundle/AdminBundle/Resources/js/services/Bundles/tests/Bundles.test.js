/* eslint-disable flowtype/require-valid-file-annotation */
import {bundleReady, bundlesReadyPromise} from '../Bundles';

afterEach(() => {
    window.BUNDLE_ENTRIES_COUNT = 0;
});

test('bundleReadyPromise should resolve when all bundles are ready', () => {
    window.BUNDLE_ENTRIES_COUNT = 3;

    bundleReady();
    bundleReady();

    setTimeout(() => {
        bundleReady();
    }, 100);

    return expect(bundlesReadyPromise).resolves.toBe(undefined);
});
