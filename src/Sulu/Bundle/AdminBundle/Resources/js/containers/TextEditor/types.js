// @flow
export type TextEditorProps = {|
    disabled: boolean,
    onBlur: () => void,
    onChange: (value: ?string) => void,
    value: ?string,
|};
