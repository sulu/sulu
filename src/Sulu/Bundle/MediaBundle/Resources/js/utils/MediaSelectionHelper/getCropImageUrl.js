// @flow
import type {Media} from '../../types';

export default function getCropImageUrl(media: Media, formats: Array<string>): ?string {
    if (formats.length === 0 || !media.mimeType.startsWith('image/')) {
        return undefined;
    }

    return media.adminUrl || media.url;
}
