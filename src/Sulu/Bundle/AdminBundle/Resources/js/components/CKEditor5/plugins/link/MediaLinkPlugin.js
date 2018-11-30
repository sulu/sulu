// @flow
import createLinkPlugin from './src/link';

import type {LinkValueType} from './types';

type MediaLinkValueType = LinkValueType & {
    value: number,
    attributes: {
        target?: string,
        download?: boolean,
    },
}

function onLink(setValue: (value: MediaLinkValueType) => void, currentValue: MediaLinkValueType) {
    const defaultValue = (currentValue && currentValue.value) || '';
    const defaultTarget = (currentValue && currentValue.attributes && currentValue.attributes.target) || '_self';
    const defaultDownload = !!(currentValue && currentValue.attributes && currentValue.attributes.download);

    const id: string = prompt('image id', defaultValue);
    const target: string = prompt('target', defaultTarget);
    const download: boolean = !!confirm('download', defaultDownload);

    const result: MediaLinkValueType = {
        value: id || null,
        attributes: {
            target: target || null,
        },
    };

    if (download) {
        result.attributes.download = id;
    }

    setValue(result);
}

export default createLinkPlugin(
    'Media Link',
    'media',
    'sulu:media',
    'id',
    'mediaLinkHref',
    onLink,
    false
);
