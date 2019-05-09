// @flow
export type ColSpan = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12;

export type BaseItemProps = {
    colSpan: ColSpan,
    spaceAfter: ColSpan,
    spaceBefore: ColSpan,
};
