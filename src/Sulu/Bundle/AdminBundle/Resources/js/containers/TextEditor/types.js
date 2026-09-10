// @flow
import type {IObservableValue} from 'mobx/lib/mobx';
import type {SchemaOptions} from '../Form/types';

export type EnterMode = 'p' | 'br';

/**
 * Must not contain anything specific to a concrete text editor implementation, so that the same config can drive a
 * different editor later.
 */
export type TextEditorConfig = {|
    enterMode: EnterMode,
    features: Array<string>,
    tags: Array<string>,
|};

export type TextEditorProps = {|
    disabled: boolean,
    locale: ?IObservableValue<string>,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    onFocus?: (event: {target: EventTarget}) => void,
    options?: ?SchemaOptions,
    value: ?string,
|};

export type TextEditorAdapterProps = {|
    ...TextEditorProps,
    config: TextEditorConfig,
|};
