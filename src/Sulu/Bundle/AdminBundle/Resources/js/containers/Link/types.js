// @flow
import type {IObservableValue} from 'mobx/lib/mobx';

export type LinkTypeOverlayProps = {|
    anchor?: ?string,
    href: ?string | number,
    locale?: ?IObservableValue<string>,
    onAnchorChange?: ?(anchor: ?string) => void,
    onCancel: () => void,
    onConfirm: () => void,
    onHrefChange: (id: ?string | number, item: ?Object) => void,
    onQueryChange?: ?(query: ?string) => void,
    onRelChange?: ?(rel: ?string) => void,
    onTargetChange?: ?(target: string) => void,
    onTitleChange?: ?(title: ?string) => void,
    open: boolean,
    options?: ?LinkTypeOptions,
    query?: ?string,
    rel?: ?string,
    target?: ?string,
    title?: ?string,
|};

export type LinkTypeOptions = {|
    displayProperties: Array<string>,
    emptyText?: string,
    icon?: string,
    listAdapter?: string,
    overlayTitle?: string,
    resourceKey: string,
    targets?: string[],
|};

export type LinkValue = {|
    anchor?: ?string,
    href: ?string | ?number,
    locale: string,
    provider: ?string,
    query?: ?string,
    rel?: ?string,
    target?: ?string,
    title: ?string,
|};
