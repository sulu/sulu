// @flow
import type {IObservableValue} from 'mobx';
import type {SchemaOptions} from '../Form/types';

export type TextEditorProps = {|
    disabled: boolean,
    locale: ?IObservableValue<string>,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    options?: ?SchemaOptions,
    value: ?string,
|};
