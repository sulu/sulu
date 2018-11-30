// @flow
import createLinkPlugin from './src/link';

import type {LinkValueType} from './types';

type InternalLinkValueType = LinkValueType & {
    attributes: {
        target?: string,
    },
}

function onLink(setValue: (value: InternalLinkValueType) => void, currentValue: InternalLinkValueType) {
    const defaultValue = (currentValue && currentValue.value) || '';
    const defaultTarget = (currentValue && currentValue.attributes && currentValue.attributes.target) || '_self';

    const uuid: string = prompt('page uuid', defaultValue);
    const target: string = prompt('target', defaultTarget);

    const result: InternalLinkValueType = {
        value: uuid || null,
        attributes: {
            target: target || null,
        },
    };

    setValue(result);
}

export default createLinkPlugin(
    'Internal Link',
    'internal',
    'sulu:link',
    'href',
    'internalLinkHref',
    onLink,
    false
);
