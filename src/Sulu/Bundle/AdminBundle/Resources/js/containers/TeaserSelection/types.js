// @flow

export type TeaserProviderOptions = {|
    displayProperties: Array<string>,
    listAdapter: string,
    overlayTitle: string,
    resourceKey: string,
    title: string,
|};

export type TeaserItem = {
    description?: ?string,
    id: number | string,
    mediaId?: number,
    title?: ?string,
    type: string,
};

export type TeaserSelectionValue = {
    displayOption: string,
    items: Array<TeaserItem>,
};
