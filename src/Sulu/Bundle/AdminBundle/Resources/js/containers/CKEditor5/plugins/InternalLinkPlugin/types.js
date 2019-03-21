// @flow
import type {IObservableValue} from 'mobx';

export type InternalLinkTypeOverlayProps = {|
    id: ?string | number,
    locale: ?IObservableValue<string>,
    onCancel: () => void,
    onConfirm: () => void,
    onIdChange: (id: ?string | number) => void,
    onTargetChange: (target: string) => void,
    open: boolean,
    options: ?Object,
    target: ?string,
|};
