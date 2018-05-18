// @flow
export type MediaItem = {
    id: string | number,
    mimeType: string,
    thumbnail: ?string,
    title: string,
};

export type Value = {
    ids: Array<string | number>,
};
