// @flow
import type {ChildrenArray, Element} from 'react';
import Divider from './Divider';
import Option from './Option';

export type OverlayListDimensions = {
    top: number,
    left: number,
    height: number,
    scrollTop: number,
}

export type OverlayListStyle = {
    top: string,
    left: string,
    height: string,
}

export type VerticalCrop = {
    dimensions: OverlayListDimensions,
    touchesTopBorder: boolean,
    touchesBottomBorder: boolean,
};

export type SelectChild = Element<typeof Option> | Element<typeof Divider>;
export type SelectChildren = ChildrenArray<SelectChild>;
