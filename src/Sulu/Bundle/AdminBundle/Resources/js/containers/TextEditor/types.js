// @flow
export type TextEditorProps = {|
    onBlur: () => void,
    onChange: (value: ?string) => void,
    value: ?string,
|};
