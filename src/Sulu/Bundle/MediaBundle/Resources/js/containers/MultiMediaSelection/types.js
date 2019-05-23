// @flow
export type Value = {
    displayOption: ?DisplayOption,
    ids: Array<number>,
};

export type DisplayOption =
    | 'leftTop'
    | 'top'
    | 'rightTop'
    | 'left'
    | 'middle'
    | 'right'
    | 'leftBottom'
    | 'bottom'
    | 'rightBottom';
