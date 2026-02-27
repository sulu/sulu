#!/usr/bin/env node
/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable no-console */
/* eslint-env node */
const fs = require('fs');

function resolveInstalledVersion(packageName) {
    try {
        const packageJsonPath = require.resolve(packageName + '/package.json', {paths: [process.cwd()]});
        const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

        return packageJson.version;
    } catch (error) {
        return undefined;
    }
}

function getMajor(version) {
    if (!version) {
        return undefined;
    }

    const match = version.match(/^(\d+)/);

    return match ? Number(match[1]) : undefined;
}

const mobxVersion = resolveInstalledVersion('mobx');
const mobxReactVersion = resolveInstalledVersion('mobx-react');

if (!mobxVersion || !mobxReactVersion) {
    console.warn(
        '[mobx-stack] Skipping compatibility check because "mobx" and/or "mobx-react" are not installed yet.'
    );
    process.exit(0);
}

const mobxMajor = getMajor(mobxVersion);
const mobxReactMajor = getMajor(mobxReactVersion);

const isValidPair =
    (mobxMajor === 4 && mobxReactMajor === 6)
    || (mobxMajor === 6 && mobxReactMajor === 7);

if (!isValidPair) {
    console.error('[mobx-stack] Unsupported dependency combination detected.');
    console.error('[mobx-stack] Installed: mobx@' + mobxVersion + ', mobx-react@' + mobxReactVersion);
    console.error('[mobx-stack] Allowed pairs: mobx@4 with mobx-react@6, or mobx@6 with mobx-react@7.');
    process.exit(1);
}

console.log(
    '[mobx-stack] Using supported dependency combination: mobx@'
    + mobxVersion
    + ', mobx-react@'
    + mobxReactVersion
    + '.'
);
