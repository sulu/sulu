// @flow
import downloadFile from '../downloadFile';

export default function downloadOriginalFile(media: {adminUrl?: ?string, url?: ?string}) {
    const url = media.adminUrl || media.url;

    if (url) {
        downloadFile(url);
    }
}
