// @flow
export type MediaItem = {
    id: string | number,
    title: string,
    mimeType: string,
    thumbnail: ?string,
};

export type Value = {
    ids: Array<string | number>,
};
