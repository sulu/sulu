// @flow
import createLinkPlugin from './src/link';

import type {LinkValueType} from './types';

type ExternalLinkValueType = LinkValueType & {
    attributes: {
        target?: string,
    },
}

function onLink(setValue: (value: ExternalLinkValueType) => void, currentValue: ExternalLinkValueType) {
    const defaultValue = (currentValue && currentValue.value) || '';
    const defaultTarget = (currentValue && currentValue.attributes && currentValue.attributes.target) || '_blank';

    const href: string = prompt('url', defaultValue);
    const target: string = prompt('target', defaultTarget);

    const result: ExternalLinkValueType = {
        value: href || null,
        attributes: {
            target: target || null,
        },
    };

    setValue(result);
}

export default createLinkPlugin(
    'External Link',
    'external',
    'a',
    'href',
    'externalLinkHref',
    onLink,
    true
);
