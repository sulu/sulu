// @flow
export default function downloadFile(url: string) {
    const link = document.createElement('a');
    link.href = url + (url.includes('?') ? '&' : '?') + 'inline=0';
    link.download = '';
    link.click();
}
