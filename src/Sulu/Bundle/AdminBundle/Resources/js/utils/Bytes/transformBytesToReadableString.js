// @flow

const transformBytesToReadableString = (bytes: number): string => {
    if (bytes === 0) {
        return '0 Byte';
    }

    const k = 1000;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
};

export default transformBytesToReadableString;
