// @flow
export type Media = {|
    id: number,
    mimeType: string,
    title: string,
    thumbnails: {[key: string]: string},
    url: string,
|};
