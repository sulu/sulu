// @flow
import type {SchemaOptions} from '../Form/types';

export type TextEditorProps = {|
    disabled: boolean,
    onBlur: () => void,
    onChange: (value: ?string) => void,
    options: ?SchemaOptions,
    value: ?string,
|};
